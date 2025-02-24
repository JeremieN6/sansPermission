<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\AIModelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AIModelRepository::class)]
#[ORM\HasLifecycleCallbacks]
class AIModel
{
    use TimestampableTrait;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_TRAINING = 'TRAINING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_ERROR = 'ERROR';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $modelId = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $trainingMetrics = null;

    #[ORM\ManyToMany(targetEntity: Episodes::class)]
    private Collection $trainingEpisodes;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $configuration = null;

    public function __construct()
    {
        $this->trainingEpisodes = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModelId(): ?string
    {
        return $this->modelId;
    }

    public function setModelId(?string $modelId): static
    {
        $this->modelId = $modelId;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, [
            self::STATUS_PENDING,
            self::STATUS_TRAINING,
            self::STATUS_COMPLETED,
            self::STATUS_ERROR
        ])) {
            throw new \InvalidArgumentException('Statut invalide');
        }
        $this->status = $status;
        return $this;
    }

    public function getTrainingMetrics(): ?string
    {
        return $this->trainingMetrics;
    }

    public function setTrainingMetrics(?string $trainingMetrics): static
    {
        $this->trainingMetrics = $trainingMetrics;
        return $this;
    }

    /**
     * @return Collection<int, Episodes>
     */
    public function getTrainingEpisodes(): Collection
    {
        return $this->trainingEpisodes;
    }

    public function addTrainingEpisode(Episodes $episode): static
    {
        if (!$this->trainingEpisodes->contains($episode)) {
            $this->trainingEpisodes->add($episode);
        }
        return $this;
    }

    public function removeTrainingEpisode(Episodes $episode): static
    {
        $this->trainingEpisodes->removeElement($episode);
        return $this;
    }

    public function getConfiguration(): ?array
    {
        return $this->configuration;
    }

    public function setConfiguration(?array $configuration): static
    {
        $this->configuration = $configuration;
        return $this;
    }
} 