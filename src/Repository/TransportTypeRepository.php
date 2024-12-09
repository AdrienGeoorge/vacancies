<?php

namespace App\Repository;

use App\Entity\TransportType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransportType>
 *
 * @method TransportType|null find($id, $lockMode = null, $lockVersion = null)
 * @method TransportType|null findOneBy(array $criteria, array $orderBy = null)
 * @method TransportType[]    findAll()
 * @method TransportType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransportTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransportType::class);
    }

    //    /**
    //     * @return TransportType[] Returns an array of TransportType objects
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

    //    public function findOneBySomeField($value): ?TransportType
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
