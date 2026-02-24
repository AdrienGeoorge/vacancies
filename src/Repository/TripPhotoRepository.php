<?php

namespace App\Repository;

use App\Entity\TripPhoto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TripPhoto>
 *
 * @method TripPhoto|null find($id, $lockMode = null, $lockVersion = null)
 * @method TripPhoto|null findOneBy(array $criteria, array $orderBy = null)
 * @method TripPhoto[]    findAll()
 * @method TripPhoto[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TripPhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TripPhoto::class);
    }
}
