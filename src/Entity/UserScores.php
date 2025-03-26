<?php

namespace App\Entity;

use App\Repository\UserScoresRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserScoresRepository::class)]
class UserScores
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userScores')]
    private ?Users $user = null;

    #[ORM\ManyToOne(inversedBy: 'userScores')]
    private ?Quizzes $quiz = null;

    #[ORM\Column(nullable: true)]
    private int $score = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getQuiz(): ?Quizzes
    {
        return $this->quiz;
    }

    public function setQuiz(?Quizzes $quiz): static
    {
        $this->quiz = $quiz;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
    public function __toString(): string
    {
        return $this->user->getPseudo() . ' - ' . $this->quiz->getTitle() . ' - ' . $this->score;
    }
}
