<?php

namespace App\Repository;

use App\Entity\Follows;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Follows>
 *
 * @method Follows|null find($id, $lockMode = null, $lockVersion = null)
 * @method Follows|null findOneBy(array $criteria, array $orderBy = null)
 * @method Follows[]    findAll()
 * @method Follows[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FollowsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Follows::class);
    }

    //    /**
    //     * @return Follows[] Returns an array of Follows objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Follows
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
