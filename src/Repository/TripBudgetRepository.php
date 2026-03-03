<?php

namespace App\Repository;

use App\Entity\TripBudget;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TripBudget>
 *
 * @method TripBudget|null find($id, $lockMode = null, $lockVersion = null)
 * @method TripBudget|null findOneBy(array $criteria, array $orderBy = null)
 * @method TripBudget[]    findAll()
 * @method TripBudget[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TripBudgetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TripBudget::class);
    }
}
