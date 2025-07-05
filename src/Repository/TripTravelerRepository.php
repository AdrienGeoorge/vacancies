<?php

namespace App\Repository;

use App\Entity\TripTraveler;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TripTraveler>
 *
 * @method TripTraveler|null find($id, $lockMode = null, $lockVersion = null)
 * @method TripTraveler|null findOneBy(array $criteria, array $orderBy = null)
 * @method TripTraveler[]    findAll()
 * @method TripTraveler[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TripTravelerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TripTraveler::class);
    }

    /**
     * @param User $user
     * @return false|mixed
     * @throws Exception
     */
    public function countTripInSolo(User $user): mixed
    {
        return $this->getEntityManager()->getConnection()->executeQuery(
            "SELECT COUNT(*)
                FROM trip_traveler t1
                LEFT JOIN trip on t1.trip_id = trip.id
                WHERE invited_id = :userId
                AND trip.departure_date IS NOT NULL
                AND trip.return_date IS NOT NULL
                AND trip.return_date < :today
                AND (SELECT COUNT(*)
                       FROM trip_traveler t2
                       WHERE t2.trip_id = t1.trip_id
                       ) = 1",
            ['userId' => $user->getId(), 'today' => (new \DateTime())->format('Y-m-d')]
        )->fetchOne();
    }

    /**
     * @param User $user
     * @return false|mixed
     * @throws Exception
     */
    public function countTripInDuo(User $user): mixed
    {
        return $this->getEntityManager()->getConnection()->executeQuery(
            "SELECT COUNT(*)
                FROM trip_traveler t1
                LEFT JOIN trip on t1.trip_id = trip.id
                WHERE invited_id = :userId
                AND trip.departure_date IS NOT NULL
                AND trip.return_date IS NOT NULL
                AND trip.return_date < :today
                AND (SELECT COUNT(*)
                       FROM trip_traveler t2
                       WHERE t2.trip_id = t1.trip_id
                       ) = 2",
            ['userId' => $user->getId(), 'today' => (new \DateTime())->format('Y-m-d')]
        )->fetchOne();
    }

    /**
     * @param User $user
     * @return false|mixed
     * @throws Exception
     */
    public function countTripInGroup(User $user): mixed
    {
        return $this->getEntityManager()->getConnection()->executeQuery(
            "SELECT COUNT(*)
                FROM trip_traveler t1
                LEFT JOIN trip on t1.trip_id = trip.id
                WHERE invited_id = :userId
                AND trip.departure_date IS NOT NULL
                AND trip.return_date IS NOT NULL
                AND trip.return_date < :today
                AND (SELECT COUNT(*)
                       FROM trip_traveler t2
                       WHERE t2.trip_id = t1.trip_id
                       ) > 2",
            ['userId' => $user->getId(), 'today' => (new \DateTime())->format('Y-m-d')]
        )->fetchOne();
    }

    /**
     * @param User $user
     * @return false|mixed
     * @throws Exception
     */
    public function countVisitedCountries(User $user): mixed
    {
        return $this->getEntityManager()->getConnection()->executeQuery(
            "SELECT count(DISTINCT country_id) as nbCountries
                FROM trip
                LEFT JOIN trip_traveler tt on trip.id = tt.trip_id
                WHERE tt.invited_id = :userId
                AND trip.departure_date IS NOT NULL
                AND trip.return_date IS NOT NULL
                AND trip.return_date < :today",
            ['userId' => $user->getId(), 'today' => (new \DateTime())->format('Y-m-d')]
        )->fetchOne();
    }

    /**
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function getVisitedContinents(User $user): array
    {
        return $this->getEntityManager()->getConnection()->executeQuery(
            "SELECT DISTINCT c.continent
                FROM trip t
                LEFT JOIN country c ON c.id = t.country_id
                WHERE t.traveler_id = :userId
                AND t.departure_date IS NOT NULL
                AND t.return_date IS NOT NULL
                AND t.return_date < :today",
            ['userId' => $user->getId(), 'today' => (new \DateTime())->format('Y-m-d')]
        )->fetchFirstColumn();
    }
}
