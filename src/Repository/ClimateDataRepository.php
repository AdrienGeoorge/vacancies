<?php

namespace App\Repository;

use App\Entity\ClimateData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ClimateDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClimateData::class);
    }

    public function findByCityAndMonth(string $city, int $month): ?ClimateData
    {
        return $this->createQueryBuilder('c')
            ->where('c.city = :city')
            ->andWhere('c.month = :month')
            ->setParameter('city', $city)
            ->setParameter('month', $month)
            ->getQuery()
            ->getOneOrNullResult();
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