<?php

namespace App\Command;

use App\Entity\Follows;
use App\Entity\PasswordReset;
use App\Entity\ShareInvitation;
use App\Entity\Trip;
use App\Entity\User;
use App\Entity\UserBadges;
use App\Entity\UserNotifications;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-disabled-accounts',
    description: 'Supprime les comptes des utilisateurs désactivés qui ont atteint le délai des 30 jours.',
)]
class CheckDisabledAccountsCommand extends Command
{
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct();
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Début du traitement de suppression des comptes désactivés depuis plus de 30 jours.');

        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNotNull('disabled'))
            ->andWhere(Criteria::expr()->lte('disabled', new \DateTime('-30 days')));

        $users = $this->managerRegistry->getRepository(User::class)->matching($criteria);

        foreach ($users as $user) $this->treatmentByUser($user);

        $this->managerRegistry->getManager()->flush();

        $io->success('Traitement terminé!');
        return Command::SUCCESS;
    }

    /**
     * @param $user
     * @return void
     */
    private function treatmentByUser($user): void
    {
        $trips = $this->managerRegistry->getRepository(Trip::class)->findBy(['traveler' => $user]);
        foreach ($trips as $trip) $this->managerRegistry->getManager()->remove($trip);

        $userBadges = $this->managerRegistry->getRepository(UserBadges::class)->findBy(['user' => $user]);
        foreach ($userBadges as $badge) $this->managerRegistry->getManager()->remove($badge);

        $following = $this->managerRegistry->getRepository(Follows::class)->findBy(['followedBy' => $user]);
        foreach ($following as $traveler) $this->managerRegistry->getManager()->remove($traveler);

        $followers = $this->managerRegistry->getRepository(Follows::class)->findBy(['follower' => $user]);
        foreach ($followers as $traveler) $this->managerRegistry->getManager()->remove($traveler);

        $passwordReset = $this->managerRegistry->getRepository(PasswordReset::class)->findBy(['user' => $user]);
        foreach ($passwordReset as $reset) $this->managerRegistry->getManager()->remove($reset);

        $invitations = $this->managerRegistry->getRepository(ShareInvitation::class)->findBy(['userToShareWith' => $user]);
        foreach ($invitations as $invitation) $this->managerRegistry->getManager()->remove($invitation);

        $notifications = $this->managerRegistry->getRepository(UserNotifications::class)->findBy(['user' => $user]);
        foreach ($notifications as $notification) $this->managerRegistry->getManager()->remove($notification);

        $notificationsByMe = $this->managerRegistry->getRepository(UserNotifications::class)->findBy(['notifiedBy' => $user]);
        foreach ($notificationsByMe as $notification) $this->managerRegistry->getManager()->remove($notification);

        $this->managerRegistry->getManager()->remove($user);
        $this->managerRegistry->getManager()->flush();
    }
}
