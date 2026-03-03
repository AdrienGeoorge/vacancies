<?php

namespace App\Repository;

use App\Entity\TripReimbursement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TripReimbursement>
 *
 * @method TripReimbursement|null find($id, $lockMode = null, $lockVersion = null)
 * @method TripReimbursement|null findOneBy(array $criteria, array $orderBy = null)
 * @method TripReimbursement[]    findAll()
 * @method TripReimbursement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TripReimbursementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TripReimbursement::class);
    }
}
