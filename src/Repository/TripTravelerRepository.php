<?php

namespace App\Repository;

use App\Entity\TripTraveler;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TripTraveler>
 *
 * @method TripTraveler|null find($id, $lockMode = null, $lockVersion = null)
 * @method TripTraveler|null findOneBy(array $criteria, array $orderBy = null)
 * @method TripTraveler[]    findAll()
 * @method TripTraveler[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TripTravelerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TripTraveler::class);
    }

    //    /**
    //     * @return OnSitePerson[] Returns an array of OnSitePerson objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?OnSitePerson
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
