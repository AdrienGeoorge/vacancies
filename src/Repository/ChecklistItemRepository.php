<?php

namespace App\Repository;

use App\Entity\ChecklistItem;
use App\Entity\Trip;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChecklistItem>
 */
class ChecklistItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChecklistItem::class);
    }

    /**
     * Returns shared items for the trip + private items owned by the user, ordered by position.
     *
     * @return ChecklistItem[]
     */
    public function findForTrip(Trip $trip, User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.trip = :trip')
            ->andWhere('c.isShared = true OR (c.isShared = false AND c.owner = :user)')
            ->setParameter('trip', $trip)
            ->setParameter('user', $user)
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
