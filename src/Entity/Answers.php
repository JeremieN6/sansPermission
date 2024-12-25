<?php

namespace App\Entity;

use App\Repository\AnswersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnswersRepository::class)]
class Answers
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'answers')]
    #[ORM\JoinColumn(name:'question_id', referencedColumnName:'id')]
    private ?Questions $questionId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isCorrect = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, UserAnswers>
     */
    #[ORM\OneToMany(targetEntity: UserAnswers::class, mappedBy: 'answerId')]
    private Collection $userAnswers;

    /**
     * @var Collection<int, UserAnswerChoices>
     */
    #[ORM\OneToMany(targetEntity: UserAnswerChoices::class, mappedBy: 'answerId')]
    private Collection $userAnswerChoices;

    public function __construct()
    {
        $this->userAnswers = new ArrayCollection();
        $this->userAnswerChoices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, UserAnswers>
     */
    public function getUserAnswers(): Collection
    {
        return $this->userAnswers;
    }

    public function addUserAnswer(UserAnswers $userAnswer): static
    {
        if (!$this->userAnswers->contains($userAnswer)) {
            $this->userAnswers->add($userAnswer);
            $userAnswer->setAnswerId($this);
        }

        return $this;
    }

    public function removeUserAnswer(UserAnswers $userAnswer): static
    {
        if ($this->userAnswers->removeElement($userAnswer)) {
            // set the owning side to null (unless already changed)
            if ($userAnswer->getAnswerId() === $this) {
                $userAnswer->setAnswerId(null);
            }
        }

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
            $userAnswerChoice->setAnswerId($this);
        }

        return $this;
    }

    public function removeUserAnswerChoice(UserAnswerChoices $userAnswerChoice): static
    {
        if ($this->userAnswerChoices->removeElement($userAnswerChoice)) {
            // set the owning side to null (unless already changed)
            if ($userAnswerChoice->getAnswerId() === $this) {
                $userAnswerChoice->setAnswerId(null);
            }
        }

        return $this;
    }
}
