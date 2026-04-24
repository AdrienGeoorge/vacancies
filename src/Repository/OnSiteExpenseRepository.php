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
     */
    public function findByTraveler(Trip $trip, TripTraveler $traveler): mixed
    {
        return $this->createQueryBuilder('ose')
            ->select("
                (CASE
                    WHEN oc.code != 'EUR' THEN ose.convertedPrice
                    ELSE ose.originalPrice
                END) as priceTotal,
                ose.convertedAt as convertedAt,
                ose.purchaseDate as purchaseDate
            ")
            ->leftJoin('ose.originalCurrency', 'oc')
            ->andWhere('ose.trip = :trip')
            ->setParameter('trip', $trip)
            ->andWhere('ose.payedBy = :traveler')
            ->setParameter('traveler', $traveler)
            ->getQuery()
            ->getResult();
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
