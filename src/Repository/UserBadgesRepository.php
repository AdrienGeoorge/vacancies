<?php

namespace App\Repository;

use App\Entity\UserBadges;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserBadges>
 *
 * @method UserBadges|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserBadges|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserBadges[]    findAll()
 * @method UserBadges[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserBadgesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBadges::class);
    }

    //    /**
    //     * @return UserBadges[] Returns an array of UserBadges objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?UserBadges
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
