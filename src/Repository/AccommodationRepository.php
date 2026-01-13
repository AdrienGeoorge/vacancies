<?php

namespace App\Repository;

use App\Entity\Accommodation;
use App\Entity\Trip;
use App\Entity\TripTraveler;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Accommodation>
 *
 * @method Accommodation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Accommodation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Accommodation[]    findAll()
 * @method Accommodation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccommodationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Accommodation::class);
    }

    public function findAllByTrip(Trip $trip)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.trip = :trip')
            ->setParameter('trip', $trip)
            ->addOrderBy('CASE WHEN a.arrivalDate IS NULL THEN 1 ELSE 0 END', 'ASC')
            ->addOrderBy('a.arrivalDate', 'ASC')
            ->addOrderBy('a.departureDate', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
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
        $result = $this->getEntityManager()->getConnection()->executeQuery(
            "SELECT (
                        SELECT SUM(
                            CASE 
                                WHEN oc1.code != 'EUR' THEN a.converted_price
                                ELSE a.original_price
                            END
                        ) +
                        SUM(
                            CASE 
                                WHEN original_deposit AND oc2.code != 'EUR' THEN a.converted_deposit
                                WHEN original_deposit AND oc2.code = 'EUR' THEN a.original_deposit
                                ELSE 0
                            END
                        )
                        FROM accommodation a 
                        LEFT JOIN currency oc1 ON a.original_currency_id = oc1.code
                        LEFT JOIN currency oc2 ON a.original_deposit_currency_id = oc2.code
                        WHERE a.trip_id = :trip AND a.payed_by_id = :traveler AND a.booked = true) as hotelTotal,
                    (
                        SELECT SUM(
                            CASE 
                                WHEN oc3.code != 'EUR' THEN ad.converted_price
                                ELSE ad.original_price
                            END
                        ) 
                        FROM accommodation_additional ad
                        JOIN accommodation a ON ad.accommodation_id = a.id
                        LEFT JOIN currency oc3 ON ad.original_currency_id = oc3.code
                        WHERE a.trip_id = :trip AND a.payed_by_id = :traveler AND a.booked = true) as additionalTotal",
            [
                'trip' => $trip->getId(),
                'traveler' => $traveler->getId()
            ])->fetchAssociative();

        return $result['hotelTotal'] + $result['additionalTotal'];
    }
}