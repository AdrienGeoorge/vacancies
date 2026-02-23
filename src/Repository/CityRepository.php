<?php

namespace App\Repository;

use App\Entity\City;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, City::class);
    }

    public function findByNameAndCountry(string $name, ?string $country): ?City
    {
        return $this->createQueryBuilder('c')
            ->where('c.name = :name')
            ->andWhere('c.country = :country')
            ->setParameter('name', $name)
            ->setParameter('country', $country)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
