<?php

namespace App\Repository;

use App\Entity\ClimateData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

class ClimateDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClimateData::class);
    }

    public function findByCityAndMonth(string $city, int $month, ?string $country = null): ?ClimateData
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.city = :city')
            ->andWhere('c.month = :month')
            ->setParameter('city', $city)
            ->setParameter('month', $month);

        if ($country !== null) {
            $qb->andWhere('c.country = :country')
                ->setParameter('country', $country);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @throws Exception
     */
    public function findNearbyCity(float $lat, float $lon, int $month, float $maxDistanceKm = 50): ?ClimateData
    {
        // Formule Haversine avec JOIN sur la table cities
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare("
            SELECT c.*,
                (6371 * acos(
                    cos(radians(:lat)) * cos(radians(ci.latitude)) *
                    cos(radians(ci.longitude) - radians(:lon)) +
                    sin(radians(:lat)) * sin(radians(ci.latitude))
                )) AS distance
            FROM climate_data c
            INNER JOIN cities ci ON ci.name = c.city AND ci.country = c.country
            WHERE c.month = :month
            HAVING distance < :maxDistance
            ORDER BY distance ASC
            LIMIT 1
        ");

        $result = $stmt->executeQuery([
            'lat' => $lat,
            'lon' => $lon,
            'month' => $month,
            'maxDistance' => $maxDistanceKm
        ]);

        $row = $result->fetchAssociative();

        if (!$row) {
            return null;
        }

        return $this->find($row['id']);
    }

    public function save(ClimateData $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ClimateData $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}