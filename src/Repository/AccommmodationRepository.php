<?php

namespace App\Repository;

use App\Entity\Accommmodation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Accommmodation>
 *
 * @method Accommmodation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Accommmodation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Accommmodation[]    findAll()
 * @method Accommmodation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccommmodationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Accommmodation::class);
    }

//    /**
//     * @return Accommmodation[] Returns an array of Accommmodation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Accommmodation
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
