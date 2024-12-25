<?php

namespace App\Entity;

use App\Repository\UserAnswersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserAnswersRepository::class)]
class UserAnswers
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userAnswers')]
    #[ORM\JoinColumn(name:'user_id', referencedColumnName:'id')]
    private ?Users $userId = null;

    #[ORM\ManyToOne(inversedBy: 'userAnswers')]
    #[ORM\JoinColumn(name:'question_id', referencedColumnName:'id')]
    private ?Questions $questionId = null;

    #[ORM\ManyToOne(inversedBy: 'userAnswers')]
    #[ORM\JoinColumn(name:'answer_id', referencedColumnName:'id')]
    private ?Answers $answerId = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isCorrect = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, UserAnswerChoices>
     */
    #[ORM\OneToMany(targetEntity: UserAnswerChoices::class, mappedBy: 'userAnswerId')]
    // #[ORM\JoinColumn(name:'user_answer_choises', referencedColumnName:'id')]
    private Collection $userAnswerChoices;

    public function __construct()
    {
        $this->userAnswerChoices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?Users
    {
        return $this->userId;
    }

    public function setUserId(?Users $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getQuestionId(): ?Questions
    {
        return $this->questionId;
    }

    public function setQuestionId(?Questions $questionId): static
    {
        $this->questionId = $questionId;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, UserAnswerChoices>
     */
    public function getUserAnswerChoices(): Collection
    {
        return $this->userAnswerChoices;
    }

    public function addUserAnswerChoice(UserAnswerChoices $userAnswerChoice): static
    {
        if (!$this->userAnswerChoices->contains($userAnswerChoice)) {
            $this->userAnswerChoices->add($userAnswerChoice);
            $userAnswerChoice->setUserAnswerId($this);
        }

        return $this;
    }

    public function removeUserAnswerChoice(UserAnswerChoices $userAnswerChoice): static
    {
        if ($this->userAnswerChoices->removeElement($userAnswerChoice)) {
            // set the owning side to null (unless already changed)
            if ($userAnswerChoice->getUserAnswerId() === $this) {
                $userAnswerChoice->setUserAnswerId(null);
            }
        }

        return $this;
    }
}
