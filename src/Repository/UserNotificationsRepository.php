<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserNotifications;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserNotifications>
 *
 * @method UserNotifications|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserNotifications|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserNotifications[]    findAll()
 * @method UserNotifications[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserNotificationsRepository extends ServiceEntityRepository
{
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNotifications::class);
        $this->managerRegistry = $registry;
    }

    /**
     * @param User $user
     * @param string $text
     * @param User|null $notifiedBy
     * @param string|null $link
     * @return void
     */
    public function sendNotification(User $user, string $text, ?User $notifiedBy = null, ?string $link = null): void
    {
        $notification = (new UserNotifications())
            ->setUser($user)
            ->setReceivedAt(new \DateTime())
            ->setView(false)
            ->setText($text)
            ->setNotifiedBy($notifiedBy)
            ->setLink($link);

        $this->managerRegistry->getManager()->persist($notification);
        $this->managerRegistry->getManager()->flush();
    }
}
