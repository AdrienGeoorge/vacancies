<?php

namespace App\Repository;

use App\Entity\PlanningEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlanningEvent>
 *
 * @method PlanningEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method PlanningEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method PlanningEvent[]    findAll()
 * @method PlanningEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlanningEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanningEvent::class);
    }

    //    /**
    //     * @return PlanningEvent[] Returns an array of PlanningEvent objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?PlanningEvent
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
