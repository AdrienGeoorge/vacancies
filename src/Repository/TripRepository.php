<?php

namespace App\Repository;

use App\Entity\Trip;
use App\Service\TextFormateService;
use App\Service\TripService;
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
    public function __construct(
        ManagerRegistry                     $registry,
        private readonly TextFormateService $textFormateService,
        private readonly TripService $tripService,
    )
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
            /**
             * @var Trip|null $result
             */
            $result = $qb->andWhere($qb->expr()->isNotNull('t.departureDate'))
                ->andWhere($qb->expr()->gte('t.departureDate', ':today'))
                ->setParameter('today', (new \DateTime())->format('Y-m-d'))
                ->orderBy('t.departureDate', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($result) {
                return [
                    'trip' => $result,
                    'destinations' => $this->tripService->getDestinations($result)
                ];
            }

            return null;
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
            /**
             * @var Trip|null $result
             */
            $result = $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();

            if ($result) {
                return [
                    'trip' => $result,
                    'destinations' => $this->tripService->getDestinations($result)
                ];
            }

            return null;
        } else {
            return $qb->getQuery()->getResult();
        }
    }

    public function getAllTrips($user): array
    {
        $today = new \DateTime('today');

        $qb = $this->createQueryBuilder('t')
            ->select('DISTINCT t.id, t.name, t.description, t.departureDate, t.returnDate, t.image')
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
            ->leftJoin('t.tripTravelers', 'tt');

        $trips = $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->eq('t.traveler', ':traveler'),
                $qb->expr()->eq('tt.invited', ':traveler')
            )
        )
            ->setParameter('traveler', $user)
            ->setParameter('today', $today)
            ->groupBy('t.id, t.name, t.description, t.departureDate, t.returnDate, t.image')
            ->orderBy('state', 'ASC')
            ->addOrderBy('t.departureDate', 'DESC')
            ->addOrderBy('t.returnDate', 'DESC')
            ->getQuery()
            ->getResult();

        if (empty($trips)) {
            return [];
        }

        $tripIds = array_column($trips, 'id');

        $countries = $this->createQueryBuilder('t2')
            ->select('t2.id AS tripId, c.name AS countryName')
            ->leftJoin('t2.destinations', 'td')
            ->leftJoin('td.country', 'c')
            ->where('t2.id IN (:tripIds)')
            ->andWhere('c.id IS NOT NULL')
            ->setParameter('tripIds', $tripIds)
            ->orderBy('td.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();

        $countriesByTrip = [];
        foreach ($countries as $country) {
            $tripId = $country['tripId'];
            if (!isset($countriesByTrip[$tripId])) {
                $countriesByTrip[$tripId] = [];
            }
            $countriesByTrip[$tripId][] = $country['countryName'];
        }

        foreach ($trips as &$trip) {
            $trip['countryName'] = isset($countriesByTrip[$trip['id']])
                ? $this->textFormateService->formatList($countriesByTrip[$trip['id']])
                : null;
        }

        return $trips;
    }

    public function getVisitedCountries($user): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('c.code, c.name, COUNT(DISTINCT t.id) as visitCount')
            ->leftJoin('t.tripTravelers', 'tt')
            ->join('t.destinations', 'td')
            ->join('td.country', 'c');

        return $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->eq('t.traveler', ':traveler'),
                $qb->expr()->eq('tt.invited', ':traveler')
            )
        )->setParameter('traveler', $user)
            ->andWhere('t.returnDate IS NOT NULL')
            ->andWhere('t.returnDate < :today')
            ->setParameter('today', (new \DateTime())->format('Y-m-d'))
            ->groupBy('c.code, c.name')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getNextCountries($user): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('DISTINCT c.code, c.name')
            ->leftJoin('t.tripTravelers', 'tt')
            ->join('t.destinations', 'td')
            ->join('td.country', 'c');

        return $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->eq('t.traveler', ':traveler'),
                $qb->expr()->eq('tt.invited', ':traveler')
            )
        )->setParameter('traveler', $user)
            ->andWhere('t.returnDate IS NOT NULL')
            ->andWhere('t.returnDate > :today')
            ->setParameter('today', (new \DateTime())->format('Y-m-d'))
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countPassedCountries($user)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(DISTINCT c.id)')
            ->leftJoin('t.tripTravelers', 'tt')
            ->leftJoin('t.destinations', 'td')
            ->leftJoin('td.country', 'c');

        return $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->eq('t.traveler', ':traveler'),
                $qb->expr()->eq('tt.invited', ':traveler')
            )
        )->setParameter('traveler', $user)
            ->andWhere('t.departureDate IS NOT NULL')
            ->andWhere('t.returnDate IS NOT NULL')
            ->andWhere('t.returnDate < :today')
            ->andWhere('c.id IS NOT NULL')
            ->setParameter('today', (new \DateTime())->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getCountryMostVisited($user)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('c.name AS countryName, COUNT(c.id) AS visitCount, MIN(t.departureDate) AS firstVisitDate')
            ->leftJoin('t.tripTravelers', 'tt')
            ->leftJoin('t.destinations', 'td')
            ->leftJoin('td.country', 'c');

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
            ->andWhere('c.id IS NOT NULL')
            ->setParameter('today', (new \DateTime())->format('Y-m-d'))
            ->groupBy('c.name')
            ->orderBy('visitCount', 'DESC')
            ->addOrderBy('firstVisitDate', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getTopCountries()
    {
        $qb = $this->createQueryBuilder('t')
            ->select('c.name AS countryName, c.code AS countryCode, COUNT(c.id) AS visitCount, MIN(t.departureDate) AS firstVisitDate')
            ->leftJoin('t.tripTravelers', 'tt')
            ->leftJoin('t.destinations', 'td')
            ->leftJoin('td.country', 'c');

        return $qb
            ->where('c.id IS NOT NULL')
            ->andWhere('t.departureDate IS NOT NULL')
            ->andWhere('t.returnDate IS NOT NULL')
            ->andWhere('t.returnDate < :today')
            ->setParameter('today', (new \DateTime())->format('Y-m-d'))
            ->groupBy('c.name, c.code')
            ->orderBy('visitCount', 'DESC')
            ->addOrderBy('firstVisitDate', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    public function getOneTrip(?int $tripId): ?array
    {
        $trip = $this->find($tripId);

        if (!$trip) return null;

        return [
            'name' => $trip->getName(),
            'description' => $trip->getDescription(),
            'departureDate' => $trip->getDepartureDate()?->format('Y-m-d'),
            'returnDate' => $trip->getReturnDate()?->format('Y-m-d'),
            'image' => $trip->getImage(),
            'ownerId' => $trip->getTraveler()?->getId(),
            'destinations' => $this->tripService->getDestinations($trip),
        ];
    }
}
