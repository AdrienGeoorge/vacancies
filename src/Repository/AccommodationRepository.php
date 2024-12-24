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
            "SELECT 
                    (SELECT SUM(a.price) 
                         FROM accommodation a 
                         WHERE a.trip_id = :trip AND a.payed_by_id = :traveler AND a.booked = true) as hotelTotal,
                    (SELECT SUM(ad.price) 
                         FROM accommodation_additional ad
                         JOIN accommodation a ON ad.accommodation_id = a.id
                         WHERE a.trip_id = :trip AND a.payed_by_id = :traveler AND a.booked = true) as additionalTotal",
            [
                'trip' => $trip->getId(),
                'traveler' => $traveler->getId()
            ])->fetchAssociative();

        return $result['hotelTotal'] + $result['additionalTotal'];
    }
}
