<?php

namespace App\Repository;

use App\Entity\Trip;
use App\Entity\TripTraveler;
use App\Entity\VariousExpensive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VariousExpensive>
 *
 * @method VariousExpensive|null find($id, $lockMode = null, $lockVersion = null)
 * @method VariousExpensive|null findOneBy(array $criteria, array $orderBy = null)
 * @method VariousExpensive[]    findAll()
 * @method VariousExpensive[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VariousExpensiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VariousExpensive::class);
    }

    /**
     * Retourne le montant des dépenses diverses effectuées par voyageur
     * @param Trip $trip
     * @param TripTraveler $traveler
     * @return mixed
     */
    public function findByTraveler(Trip $trip, TripTraveler $traveler): mixed
    {
        return $this->createQueryBuilder('ve')
            ->select('SUM(CASE 
                    WHEN ve.perPerson = true THEN ve.price * :nbTraveler
                    ELSE ve.price 
                 END) as totalPrice')
            ->setParameter('nbTraveler', $trip->getTripTravelers()->count())
            ->andWhere('ve.trip = :trip')
            ->setParameter('trip', $trip)
            ->andWhere('ve.payedBy = :traveler')
            ->setParameter('traveler', $traveler)
            ->andWhere('ve.paid = true')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
