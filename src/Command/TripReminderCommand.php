<?php

namespace App\Command;

use App\Entity\TripBudget;
use App\Repository\TripBudgetRepository;
use App\Repository\TripRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'app:trip-reminder',
    description: 'Envoie les rappels J-7 et J-1 avant le départ aux voyageurs.',
)]
class TripReminderCommand extends Command
{
    public function __construct(
        private readonly TripRepository $tripRepository,
        private readonly TripBudgetRepository $tripBudgetRepository,
        private readonly MailerInterface $mailer,
        private readonly string $domain,
        private readonly string $fromMail,
        private readonly string $appName,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Envoi des rappels de voyage...');

        $sent = 0;

        foreach ([7, 1] as $days) {
            $trips = $this->tripRepository->findTripsForReminder($days);

            foreach ($trips as $trip) {
                $nbTravelers = max(1, $trip->getTripTravelers()->count());

                $onSiteBudget = $this->tripBudgetRepository->findOneBy(['trip' => $trip, 'category' => 'on-site']);
                $onSiteBudgetPerPerson = ($onSiteBudget?->getAmount() !== null)
                    ? round($onSiteBudget->getAmount() / $nbTravelers, 2)
                    : null;

                $unbookedActivities = $trip->getActivities()->filter(
                    fn($activity) => !$activity->isBooked()
                );

                foreach ($trip->getTripTravelers() as $traveler) {
                    $user = $traveler->getInvited();

                    if ($user === null || !$user->isReceiveReminderEmails()) {
                        continue;
                    }

                    $sharedItems = $trip->getChecklistItems()->filter(
                        fn($item) => $item->isShared() && !$item->isChecked()
                    );

                    $personalItems = $trip->getChecklistItems()->filter(
                        fn($item) => !$item->isShared() && !$item->isChecked() && $item->getOwner()?->getId() === $user->getId()
                    );

                    $email = (new TemplatedEmail())
                        ->from($this->fromMail)
                        ->to($user->getEmail())
                        ->subject($this->appName . ' : votre départ approche dans ' . $days . ' jour' . ($days > 1 ? 's' : '') . ' !')
                        ->htmlTemplate('trip/reminder-mail.html.twig')
                        ->context([
                            'trip' => $trip,
                            'user' => $user,
                            'days' => $days,
                            'sharedItems' => $sharedItems,
                            'personalItems' => $personalItems,
                            'unbookedActivities' => $unbookedActivities,
                            'onSiteBudgetPerPerson' => $onSiteBudgetPerPerson,
                            'domain' => $this->domain,
                            'app_name' => $this->appName,
                        ]);

                    $this->mailer->send($email);
                    $sent++;
                }
            }
        }

        $io->success("Rappels envoyés : {$sent}");
        return Command::SUCCESS;
    }
}
