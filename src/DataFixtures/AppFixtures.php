<?php

namespace App\DataFixtures;

use App\Entity\Users;
use App\Entity\Questions;
use App\Entity\Answers;
use App\Entity\UserAnswers;
use App\Entity\UserAnswerChoices;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;



class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

                // Création des utilisateurs
                $user1 = new Users();
                $user1->setNom('Ngoyi');
                $user1->setPrenom('Jérémie');
                $user1->setAge(28);
                $user1->setPseudo('JeremFut7');
                $user1->setRoles(['ROLE_ADMIN']);
                $user1->setEmail('ngoyi.jeremie@gmail.com');
                // $user1->setPassword($this->passwordHasher->hash('password123'));
                $user1->setPassword($this->passwordHasher->hashPassword($user1, 'password123'));
                $user1->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($user1);
        
                $user2 = new Users();
                $user2->setNom('nomUser2');
                $user2->setPrenom('prenomUser2');
                $user2->setAge(27);
                $user2->setPseudo('user2');
                $user2->setEmail('user2@example.com');
                // $user2->setPassword($this->passwordHasher->hash('password456'));
                $user2->setPassword($this->passwordHasher->hashPassword($user2, 'password456'));
                $user2->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($user2);
        
                $user3 = new Users();
                $user3->setNom('nomUser3');
                $user3->setPrenom('prenomUser3');
                $user3->setAge(26);
                $user3->setPseudo('user3');
                $user3->setEmail('user3@example.com');
                // $user3->setPassword($this->passwordHasher->hash('password789'));
                $user3->setPassword($this->passwordHasher->hashPassword($user3, 'password789'));
                $user3->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($user3);
        
                // Création des questions
                $question1 = new Questions();
                $question1->setContent('Quelle est la capitale de la France ?');
                $question1->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($question1);
        
                $question2 = new Questions();
                $question2->setContent('Combien de continents y a-t-il ?');
                $question2->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($question2);
        
                $question3 = new Questions();
                $question3->setContent('Quel est l\'animal le plus rapide ?');
                $question3->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($question3);
        
                // Création des réponses
                $answer1 = new Answers();
                $answer1->setQuestionId($question1);
                $answer1->setContent('Paris');
                $answer1->setCorrect(true);
                $answer1->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($answer1);
        
                $answer2 = new Answers();
                $answer2->setQuestionId($question1);
                $answer2->setContent('Lyon');
                $answer2->setCorrect(false);
                $answer2->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($answer2);
        
                $answer3 = new Answers();
                $answer3->setQuestionId($question1);
                $answer3->setContent('Marseille');
                $answer3->setCorrect(false);
                $answer3->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($answer3);
        
                $answer4 = new Answers();
                $answer4->setQuestionId($question2);
                $answer4->setContent('5');
                $answer4->setCorrect(false);
                $answer4->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($answer4);
        
                $answer5 = new Answers();
                $answer5->setQuestionId($question2);
                $answer5->setContent('6');
                $answer5->setCorrect(true);
                $answer5->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($answer5);
        
                $answer6 = new Answers();
                $answer6->setQuestionId($question2);
                $answer6->setContent('7');
                $answer6->setCorrect(false);
                $answer6->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($answer6);
        
                $answer7 = new Answers();
                $answer7->setQuestionId($question3);
                $answer7->setContent('Guépard');
                $answer7->setCorrect(true);
                $answer7->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($answer7);
        
                $answer8 = new Answers();
                $answer8->setQuestionId($question3);
                $answer8->setContent('Lion');
                $answer8->setCorrect(false);
                $answer8->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($answer8);
        
                $answer9 = new Answers();
                $answer9->setQuestionId($question3);
                $answer9->setContent('Aigle');
                $answer9->setCorrect(false);
                $answer9->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($answer9);
        
                // Création des réponses des utilisateurs
                $userAnswer1 = new UserAnswers();
                $userAnswer1->setUserId($user1);
                $userAnswer1->setQuestionId($question1);
                $userAnswer1->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($userAnswer1);
        
                $userAnswer2 = new UserAnswers();
                $userAnswer2->setUserId($user1);
                $userAnswer2->setQuestionId($question2);
                $userAnswer2->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($userAnswer2);
        
                $userAnswer3 = new UserAnswers();
                $userAnswer3->setUserId($user2);
                $userAnswer3->setQuestionId($question1);
                $userAnswer3->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($userAnswer3);
        
                $userAnswer4 = new UserAnswers();
                $userAnswer4->setUserId($user2);
                $userAnswer4->setQuestionId($question3);
                $userAnswer4->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($userAnswer4);
        
                $userAnswer5 = new UserAnswers();
                $userAnswer5->setUserId($user3);
                $userAnswer5->setQuestionId($question2);
                $userAnswer5->setCreatedAt(new \DateTimeImmutable());
                $manager->persist($userAnswer5);
        
                // Création des choix de réponses des utilisateurs
                $userAnswerChoice1 = new UserAnswerChoices();
                $userAnswerChoice1->setUserAnswerId($userAnswer1);
                $userAnswerChoice1->setAnswerId($answer1);
                $userAnswerChoice1->setCorrect(true);
                $manager->persist($userAnswerChoice1);
        
                $userAnswerChoice2 = new UserAnswerChoices();
                $userAnswerChoice2->setUserAnswerId($userAnswer1);
                $userAnswerChoice2->setAnswerId($answer2);
                $userAnswerChoice2->setCorrect(false);
                $manager->persist($userAnswerChoice2);
        
                $userAnswerChoice3 = new UserAnswerChoices();
                $userAnswerChoice3->setUserAnswerId($userAnswer2);
                $userAnswerChoice3->setAnswerId($answer4);
                $userAnswerChoice3->setCorrect(false);
                $manager->persist($userAnswerChoice3);
        
                $userAnswerChoice4 = new UserAnswerChoices();
                $userAnswerChoice4->setUserAnswerId($userAnswer2);
                $userAnswerChoice4->setAnswerId($answer5);
                $userAnswerChoice4->setCorrect(true);
                $manager->persist($userAnswerChoice4);
        
                $userAnswerChoice5 = new UserAnswerChoices();
                $userAnswerChoice5->setUserAnswerId($userAnswer3);
                $userAnswerChoice5->setAnswerId($answer1);
                $userAnswerChoice5->setCorrect(true);
                $manager->persist($userAnswerChoice5);
        
                $userAnswerChoice6 = new UserAnswerChoices();
                $userAnswerChoice6->setUserAnswerId($userAnswer4);
                $userAnswerChoice6->setAnswerId($answer7);
                $userAnswerChoice6->setCorrect(true);
                $manager->persist($userAnswerChoice6);
        
                $userAnswerChoice7 = new UserAnswerChoices();
                $userAnswerChoice7->setUserAnswerId($userAnswer5);
                $userAnswerChoice7->setAnswerId($answer6);
                $userAnswerChoice7->setCorrect(false);
                $manager->persist($userAnswerChoice7);

        $manager->flush();
    }
}
