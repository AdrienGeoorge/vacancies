<?php

namespace App\Repository;

use App\Entity\Transport;
use App\Entity\Trip;
use App\Entity\TripTraveler;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transport>
 *
 * @method Transport|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transport|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transport[]    findAll()
 * @method Transport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transport::class);
    }

    /**
     * Retourne le montant des transports réservés par voyageur
     * @param Trip $trip
     * @param TripTraveler $traveler
     * @return mixed
     */
    public function findByTraveler(Trip $trip, TripTraveler $traveler): mixed
    {
        return $this->createQueryBuilder('t')
            ->select('SUM(CASE 
                    WHEN t.perPerson = true THEN t.price * :nbTraveler
                    ELSE t.price 
                 END) as totalPrice')
            ->setParameter('nbTraveler', $trip->getTripTravelers()->count())
            ->andWhere('t.trip = :trip')
            ->setParameter('trip', $trip)
            ->andWhere('t.payedBy = :traveler')
            ->setParameter('traveler', $traveler)
            ->andWhere('t.paid = true')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
