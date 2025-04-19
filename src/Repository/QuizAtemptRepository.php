<?php

namespace App\Repository;

use App\Entity\QuizAtempt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuizAtempt>
 */
class QuizAtemptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuizAtempt::class);
    }

    //    /**
    //     * @return QuizAtempt[] Returns an array of QuizAtempt objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('q')
    //            ->andWhere('q.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('q.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?QuizAtempt
    //    {
    //        return $this->createQueryBuilder('q')
    //            ->andWhere('q.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
