<?php

namespace App\Repository;

use App\Entity\TripDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TripDocument>
 *
 * @method TripDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method TripDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method TripDocument[]    findAll()
 * @method TripDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TripDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TripDocument::class);
    }

//    /**
//     * @return TripDocument[] Returns an array of TripDocument objects
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

//    public function findOneBySomeField($value): ?TripDocument
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
