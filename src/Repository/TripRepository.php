<?php

namespace App\Repository;

use App\Entity\Trip;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Trip>
 *
 * @method Trip|null find($id, $lockMode = null, $lockVersion = null)
 * @method Trip|null findOneBy(array $criteria, array $orderBy = null)
 * @method Trip[]    findAll()
 * @method Trip[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TripRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trip::class);
    }

    public function getFutureTrips($user, bool $nextOnly = false)
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.tripTravelers', 'tt');

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->eq('t.traveler', ':traveler'),
                $qb->expr()->eq('tt.invited', ':traveler')
            )
        )->setParameter('traveler', $user);

        if ($nextOnly) {
            return $qb->andWhere($qb->expr()->isNotNull('t.departureDate'))
                ->andWhere($qb->expr()->gte('t.departureDate', ':today'))
                ->setParameter('today', (new \DateTime())->format('Y-m-d'))
                ->orderBy('t.departureDate', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        } else {
            return $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->gte('t.returnDate', ':today'),
                    $qb->expr()->isNull('t.departureDate'),
                    $qb->expr()->isNull('t.returnDate')
                )
            )->setParameter('today', (new \DateTime())->format('Y-m-d'))
                ->orderBy("CASE WHEN t.departureDate IS NULL THEN 1 ELSE 0 END", 'ASC')
                ->addOrderBy('t.departureDate', 'ASC')
                ->addOrderBy('t.id', 'DESC')
                ->getQuery()
                ->getResult();
        }
    }

    public function getPassedTrips($user, bool $lastOnly = false)
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.tripTravelers', 'tt');

        $qb = $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->eq('t.traveler', ':traveler'),
                $qb->expr()->eq('tt.invited', ':traveler')
            )
        )->setParameter('traveler', $user)
            ->andWhere('t.departureDate IS NOT NULL')
            ->andWhere('t.returnDate IS NOT NULL')
            ->andWhere('t.returnDate < :today')
            ->setParameter('today', (new \DateTime())->format('Y-m-d'))
            ->orderBy('t.departureDate', 'DESC');

        if ($lastOnly) {
            return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
        } else {
            return $qb->getQuery()->getResult();
        }
    }

    public function getAllTrips($user): array
    {
        $today = new \DateTime('today');

        $qb = $this->createQueryBuilder('t')
            ->select('DISTINCT t.id, t.name, t.description, t.departureDate, t.returnDate, t.image, c.name AS countryName')
            ->addSelect("
                CASE
                    WHEN (t.departureDate IS NOT NULL
                         AND t.returnDate IS NOT NULL
                         AND t.departureDate <= :today
                         AND t.returnDate >= :today)
                      OR (t.departureDate IS NOT NULL AND t.returnDate IS NULL
                         AND t.departureDate <= :today)
                    THEN 1 -- en cours
                        
                    WHEN (t.departureDate IS NOT NULL AND t.returnDate IS NOT NULL AND t.returnDate > :today)
                      OR (t.departureDate IS NOT NULL AND t.returnDate IS NULL AND t.departureDate > :today)
                        THEN 2 -- à venir
            
                    WHEN (t.departureDate IS NULL AND t.returnDate IS NULL)
                      OR (t.departureDate IS NOT NULL AND t.returnDate IS NULL)
                      OR (t.departureDate IS NULL AND t.returnDate IS NOT NULL)
                        THEN 3 -- non planifié
            
                    WHEN t.departureDate IS NOT NULL
                     AND t.returnDate IS NOT NULL
                     AND t.returnDate < :today
                        THEN 4 -- passé
            
                    ELSE 4
                END AS state
            ")
            ->leftJoin('t.tripTravelers', 'tt')
            ->leftJoin('t.country', 'c');

        return $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->eq('t.traveler', ':traveler'),
                $qb->expr()->eq('tt.invited', ':traveler')
            )
        )
            ->setParameter('traveler', $user)
            ->setParameter('today', $today)
            ->orderBy('state', 'ASC')
            ->addOrderBy('t.departureDate', 'DESC')
            ->addOrderBy('t.returnDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countPassedCountries($user)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(DISTINCT t.country)')
            ->leftJoin('t.tripTravelers', 'tt');

        return $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->eq('t.traveler', ':traveler'),
                $qb->expr()->eq('tt.invited', ':traveler')
            )
        )->setParameter('traveler', $user)
            ->andWhere('t.departureDate IS NOT NULL')
            ->andWhere('t.returnDate IS NOT NULL')
            ->andWhere('t.returnDate < :today')
            ->setParameter('today', (new \DateTime())->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getCountryMostVisited($user)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('country.name AS countryName, COUNT(DISTINCT country.code) AS visitCount, MIN(t.departureDate) AS firstVisitDate')
            ->leftJoin('t.tripTravelers', 'tt')
            ->leftJoin('t.country', 'country');

        return $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->eq('t.traveler', ':traveler'),
                $qb->expr()->eq('tt.invited', ':traveler')
            )
        )
            ->setParameter('traveler', $user)
            ->andWhere('t.departureDate IS NOT NULL')
            ->andWhere('t.returnDate IS NOT NULL')
            ->andWhere('t.returnDate < :today')
            ->setParameter('today', (new \DateTime())->format('Y-m-d'))
            ->groupBy('country.name')
            ->orderBy('visitCount', 'DESC')
            ->addOrderBy('firstVisitDate', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getTopCountries()
    {
        $qb = $this->createQueryBuilder('t')
            ->select('country.name AS countryName, country.code AS countryCode, COUNT(DISTINCT country.code) AS visitCount, MIN(t.departureDate) AS firstVisitDate')
            ->leftJoin('t.tripTravelers', 'tt')
            ->leftJoin('t.country', 'country');

        return $qb
            ->where('t.country IS NOT NULL')
            ->andWhere('t.departureDate IS NOT NULL')
            ->andWhere('t.returnDate IS NOT NULL')
            ->andWhere('t.returnDate < :today')
            ->setParameter('today', (new \DateTime())->format('Y-m-d'))
            ->groupBy('country.name, country.code')
            ->orderBy('visitCount', 'DESC')
            ->addOrderBy('firstVisitDate', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    public function getOneTrip(?int $tripId)
    {
        return $this->createQueryBuilder('t')
            ->select('t.name, t.description, t.departureDate, t.returnDate, t.image, c.code AS selectedCountry, owner.id AS ownerId')
            ->leftJoin('t.traveler', 'owner')
            ->leftJoin('t.country', 'c')
            ->andWhere('t.id = :id')
            ->setParameter('id', $tripId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
