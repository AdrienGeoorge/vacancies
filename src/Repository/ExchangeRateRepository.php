<?php

namespace App\Repository;

use App\Entity\ExchangeRate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExchangeRate>
 */
class ExchangeRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExchangeRate::class);
    }

    /**
     * Récupère les taux du jour (ou les plus récents)
     */
    public function getLatestRates(): ?ExchangeRate
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère les taux pour une date spécifique
     */
    public function getRatesForDate(\DateTimeInterface $date): ?ExchangeRate
    {
        return $this->createQueryBuilder('e')
            ->where('e.date = :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère les taux les plus proches d'une date donnée (pour l'historique)
     */
    public function getClosestRates(\DateTimeInterface $date): ?ExchangeRate
    {
        return $this->createQueryBuilder('e')
            ->where('e.date <= :date')
            ->setParameter('date', $date)
            ->orderBy('e.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
