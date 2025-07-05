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

    /**
     * @return Trip[]
     */
    public function getFutureTrips($user): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.tripTravelers', 'tt');

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->eq('t.traveler', ':traveler'),
                $qb->expr()->eq('tt.invited', ':traveler')
            )
        )->setParameter('traveler', $user);

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

    public function getPassedTrips($user): array
    {
        $qb = $this->createQueryBuilder('t')
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
            ->orderBy('t.departureDate', 'DESC')
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
}
