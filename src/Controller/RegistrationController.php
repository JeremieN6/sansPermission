<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegistrationFormType;
use App\Repository\UsersRepository;
use App\Security\UsersAuthenticator;
use App\Service\JWTService;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        Security $security, 
        EntityManagerInterface $entityManager,
        SendMailService $mail,
        JWTService $jwt): Response
    {
        $user = new Users();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email

            //On génère le jwt de l'utilisateur
            //On crée le header
            $header = [
                'type' => 'JWT',
                'alg' => 'HS256'
            ];

            //On crée le payload
            $payload = [
                'user_id' => $user->getId()
            ];

            //On génère le token
            $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));
            //On envoie un mail
            $mail->send(
                'no-replay@j-stream.fr',
                $user->getEmail(),
                'Activation de votre compte',
                'register',
                [
                    'user' => $user,
                    'token' =>$token
                ]
            );

            return $security->login($user, UsersAuthenticator::class, 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verification/{token}', name:'verification_user')]
    public function verifyUser($token, JWTService $jwt, UsersRepository $usersRepository, entityManagerInterface $em): Response
    {
        //On vérifie si le token est valide, n'a pas expiré et n'a pas été modifié
        if($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret')))
        {
            //On récupère le Payload
            $payload = $jwt->getPayload($token);

            //On récupère le user du token
            $user = $usersRepository->find($payload['user_id']);

            ///On vérifie si le user n'a pas encore activé son compte
            if($user && !$user->getisVerified())
            {
                $user->setisVerified(true);
                $em->flush($user);
                $this->addFlash('succes', 'Utilisateur activé 🚀 !');
                return $this->redirectToRoute('app_main');
            }
        }
        //Ici un problème se pose sur le token
        $this->addFlash('danger', 'Le token est invalid, ou à expiré !');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/renvoieverif', name:'resend_verif')]
    public function resendVerif(JWTService $jwt, SendMailService $mail, UsersRepository $usersRepository): Response
    {
        $user = $this->getUser();

        if(!$user){
            $this->addFlash('danger', 'Vous devez être connecté pour accéder à cette page ⛔ !');
            return $this->redirectToRoute('app_login');
        }

        if($user->getIsVerified())
        {
            $this->addFlash('warring', 'Cet utilisateur est déja activé !');
            return $this->redirectToRoute('app_main');
        }

            //On génère le jwt de l'utilisateur
            //On crée le header
            $header = [
                'type' => 'JWT',
                'alg' => 'HS256'
            ];

            //On crée le payload
            $payload = [
                'user_id' => $user->getId()
            ];

            //On génère le token
            $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));
            //On envoie un mail
            $mail->send(
                'no-replay@j-stream.fr',
                $user->getEmail(),
                'Activation de votre compte',
                'register',
                [
                    'user' => $user,
                    'token' =>$token
                ]
            );

            $this->addFlash('success', 'Email de vérification envoyé ✅ !');
            return $this->redirectToRoute('app_main');

    }
}
