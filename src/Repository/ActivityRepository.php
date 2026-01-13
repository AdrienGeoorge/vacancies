<?php

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\Trip;
use App\Entity\TripTraveler;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 *
 * @method Activity|null find($id, $lockMode = null, $lockVersion = null)
 * @method Activity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Activity[]    findAll()
 * @method Activity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    /**
     * Retourne le montant des activités réservées par voyageur
     * @param Trip $trip
     * @param TripTraveler $traveler
     * @return mixed
     */
    public function findByTraveler(Trip $trip, TripTraveler $traveler): mixed
    {
        return $this->createQueryBuilder('a')
            ->select("SUM(CASE 
                    WHEN a.perPerson = true AND oc.code != 'EUR' THEN a.convertedPrice * :nbTraveler
                    WHEN a.perPerson = true AND oc.code = 'EUR' THEN a.originalPrice * :nbTraveler
                    WHEN a.perPerson = false AND oc.code != 'EUR' THEN a.convertedPrice
                    ELSE a.originalPrice
                 END) as totalPrice")
            ->leftJoin('a.originalCurrency', 'oc')
            ->setParameter('nbTraveler', $trip->getTripTravelers()->count())
            ->andWhere('a.trip = :trip')
            ->setParameter('trip', $trip)
            ->andWhere('a.payedBy = :traveler')
            ->setParameter('traveler', $traveler)
            ->andWhere('a.booked = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllByTrip(Trip $trip)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.trip = :trip')
            ->setParameter('trip', $trip)
            ->addOrderBy('CASE WHEN a.date IS NULL THEN 1 ELSE 0 END', 'ASC')
            ->addOrderBy('a.date', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
