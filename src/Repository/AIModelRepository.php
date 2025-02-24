<?php

namespace App\Repository;

use App\Entity\AIModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AIModel>
 *
 * @method AIModel|null find($id, $lockMode = null, $lockVersion = null)
 * @method AIModel|null findOneBy(array $criteria, array $orderBy = null)
 * @method AIModel[]    findAll()
 * @method AIModel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AIModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AIModel::class);
    }

    /**
     * Trouve les modèles en cours d'entraînement
     *
     * @return AIModel[]
     */
    public function findTrainingModels(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', AIModel::STATUS_TRAINING)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve le dernier modèle complété avec succès
     */
    public function findLastCompletedModel(): ?AIModel
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', AIModel::STATUS_COMPLETED)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve tous les modèles avec leurs métriques d'entraînement
     *
     * @return AIModel[]
     */
    public function findAllWithMetrics(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.trainingMetrics IS NOT NULL')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 