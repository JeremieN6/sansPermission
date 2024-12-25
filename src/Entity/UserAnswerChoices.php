<?php

namespace App\Entity;

use App\Repository\UserAnswerChoicesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserAnswerChoicesRepository::class)]
class UserAnswerChoices
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userAnswerChoices')]
    #[ORM\JoinColumn(name:'user_answer_id', referencedColumnName:'id')]
    private ?UserAnswers $userAnswerId = null;

    #[ORM\ManyToOne(inversedBy: 'userAnswerChoices')]
    #[ORM\JoinColumn(name:'answer_id', referencedColumnName:'id')]
    private ?Answers $answerId = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isCorrect = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserAnswerId(): ?UserAnswers
    {
        return $this->userAnswerId;
    }

    public function setUserAnswerId(?UserAnswers $userAnswerId): static
    {
        $this->userAnswerId = $userAnswerId;

        return $this;
    }

    public function getAnswerId(): ?Answers
    {
        return $this->answerId;
    }

    public function setAnswerId(?Answers $answerId): static
    {
        $this->answerId = $answerId;

        return $this;
    }

    public function isCorrect(): ?bool
    {
        return $this->isCorrect;
    }

    public function setCorrect(?bool $isCorrect): static
    {
        $this->isCorrect = $isCorrect;

        return $this;
    }
}
