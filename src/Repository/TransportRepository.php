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

    public function findAllByTrip(Trip $trip)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.trip = :trip')
            ->setParameter('trip', $trip)
            ->addOrderBy('CASE WHEN t.arrivalDate IS NULL THEN 1 ELSE 0 END', 'ASC')
            ->addOrderBy('t.departureDate', 'ASC')
            ->addOrderBy('t.arrivalDate', 'ASC')
            ->addOrderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

}
