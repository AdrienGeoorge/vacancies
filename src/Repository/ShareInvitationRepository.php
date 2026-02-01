<?php

namespace App\Repository;

use App\Entity\ShareInvitation;
use App\Entity\Trip;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShareInvitation>
 *
 * @method ShareInvitation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShareInvitation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShareInvitation[]    findAll()
 * @method ShareInvitation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShareInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShareInvitation::class);
    }

    public function getInvitationByUserOrMail(?User $user, ?string $mail, Trip $trip)
    {
        $queryBuilder = $this->createQueryBuilder('s');

        $orX = $queryBuilder->expr()->orX(
            $queryBuilder->expr()->eq('s.userToShareWith', ':user'),
            $queryBuilder->expr()->eq('s.email', ':mail')
        );

        return $queryBuilder
            ->andWhere($orX)
            ->setParameter('user', $user)
            ->setParameter('mail', $mail)
            ->andWhere('s.trip = :trip')
            ->setParameter('trip', $trip)
            ->andWhere('s.expireAt > :now')
            ->setParameter('now', new \DateTimeImmutable('now'))
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return ShareInvitation[] Returns an array of ShareInvitation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ShareInvitation
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
