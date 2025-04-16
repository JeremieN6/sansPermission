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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, UsersAuthenticator $authenticator, EntityManagerInterface $entityManager, SendMailService $mail, JWTService $jwt): Response
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
                'contact@sanspermission.fr',
                $user->getEmail(),
                'Activation de votre compte',
                'register',
                [
                    'user' => $user,
                    'token' =>$token
                ]
            );


            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verification/{token}', name:'verification_user')]
    public function verifyUser($token, JWTService $jwt, UsersRepository $usersRepository, entityManagerInterface $em,\MercurySeries\FlashyBundle\FlashyNotifier $flashy): Response
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
                // $flashy->success('Félicitations ! Votre compte est activé 🚀 !');
                return $this->redirectToRoute('app_home');
            }
        }
        //Ici un problème se pose sur le token
        // $flashy->error('Le token est invalid, ou à expiré ⛔!');
        $this->addFlash('danger', 'Le token est invalid, ou à expiré ⛔!');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/renvoieverif', name:'resend_verif')]
    public function resendVerif(JWTService $jwt, SendMailService $mail, UsersRepository $usersRepository): Response
    {
        $user = $this->getUser();

        if(!$user){
            // $flashy->error('Vous devez être connecté pour accéder à cette page ⛔ !');
            $this->addFlash('danger', 'Vous devez être connecté pour accéder à cette page ⛔ !');
            return $this->redirectToRoute('app_login');
        }

        if($user->getIsVerified())
        {
            // $flashy->warning('Le compte utilisateur est déja activé !', '');
            $this->addFlash('warring', 'Cet utilisateur est déja activé !');
            return $this->redirectToRoute('app_home');
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
                'contact@sanspermission.fr',
                $user->getEmail(),
                'Activation de votre compte',
                'register',
                [
                    'user' => $user,
                    'token' =>$token
                ]
            );

            // $flashy->success('Email de vérification envoyé ✅ !');

            $this->addFlash('success', 'Email de vérification envoyé ✅ !');
            return $this->redirectToRoute('app_home');

    }
}
