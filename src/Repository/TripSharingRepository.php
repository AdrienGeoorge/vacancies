<?php

namespace App\Repository;

use App\Entity\TripSharing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TripSharing>
 *
 * @method TripSharing|null find($id, $lockMode = null, $lockVersion = null)
 * @method TripSharing|null findOneBy(array $criteria, array $orderBy = null)
 * @method TripSharing[]    findAll()
 * @method TripSharing[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TripSharingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TripSharing::class);
    }

    //    /**
    //     * @return TripSharing[] Returns an array of TripSharing objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?TripSharing
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
