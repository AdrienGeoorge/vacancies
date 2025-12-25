<?php

namespace App\Repository;

use App\Entity\OnSiteExpense;
use App\Entity\Trip;
use App\Entity\TripTraveler;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OnSiteExpense>
 *
 * @method OnSiteExpense|null find($id, $lockMode = null, $lockVersion = null)
 * @method OnSiteExpense|null findOneBy(array $criteria, array $orderBy = null)
 * @method OnSiteExpense[]    findAll()
 * @method OnSiteExpense[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OnSiteExpenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OnSiteExpense::class);
    }

    /**
     * Retourne le montant des hôtels réservés par voyageur
     * @param Trip $trip
     * @param TripTraveler $traveler
     * @return mixed
     * @throws Exception
     */
    public function findByTraveler(Trip $trip, TripTraveler $traveler): mixed
    {
        return $this->createQueryBuilder('ose')
            ->select('SUM(ose.price) as totalPrice')
            ->andWhere('ose.trip = :trip')
            ->setParameter('trip', $trip)
            ->andWhere('ose.payedBy = :traveler')
            ->setParameter('traveler', $traveler)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllByTrip(Trip $trip)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.trip = :trip')
            ->setParameter('trip', $trip)
            ->addOrderBy('o.purchaseDate', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
