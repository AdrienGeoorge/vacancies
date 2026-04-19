<?php

namespace App\Command;

use App\Entity\TripBudget;
use App\Repository\TripRepository;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:trip-summary',
    description: 'Envoie le résumé de fin de voyage aux voyageurs le lendemain du retour.',
)]
class TripSummaryCommand extends Command
{
    public function __construct(
        private readonly TripRepository $tripRepository,
        private readonly TripService $tripService,
        private readonly MailerInterface $mailer,
        private readonly ManagerRegistry $managerRegistry,
        private readonly TranslatorInterface $translator,
        private readonly string $domain,
        private readonly string $fromMail,
        private readonly string $appName,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Envoi des résumés de fin de voyage...');

        $trips = $this->tripRepository->findTripsEndedYesterday();
        $sent = 0;

        foreach ($trips as $trip) {
            $budget = $this->tripService->getBudget($trip);
            $nbTravelers = max(1, $trip->getTripTravelers()->count());
            $totalPerPerson = round($budget['total'] / $nbTravelers, 2);

            // Budgets prévisionnels par catégorie
            $tripBudgets = $this->managerRegistry->getRepository(TripBudget::class)->findBy(['trip' => $trip]);
            $plannedByCategory = [];
            $plannedTotal = 0;
            foreach ($tripBudgets as $tb) {
                if ($tb->getAmount() !== null) {
                    $plannedByCategory[$tb->getCategory()] = $tb->getAmount();
                    $plannedTotal += $tb->getAmount();
                }
            }

            foreach ($trip->getTripTravelers() as $traveler) {
                $user = $traveler->getInvited();

                if ($user === null || !$user->isReceiveSummaryEmails()) {
                    continue;
                }

                $locale = $user->getLanguage() ?? 'fr';
                $t = fn(string $key, array $params = []) => $this->translator->trans($key, $params, 'messages', $locale);

                $categoryMap = [
                    ['key' => 'transports',        'label' => $t('trip.summary.category.transports')],
                    ['key' => 'accommodations',     'label' => $t('trip.summary.category.accommodations')],
                    ['key' => 'activities',         'label' => $t('trip.summary.category.activities')],
                    ['key' => 'various-expensive',  'label' => $t('trip.summary.category.various_expensive')],
                ];

                $budgetCategories = [];
                foreach ($categoryMap as $cat) {
                    $amount = round(
                        ($budget['details']['reserved'][$cat['key']]['amount'] ?? 0)
                        + ($budget['details']['nonReserved'][$cat['key']] ?? 0),
                        2
                    );
                    if ($amount > 0) {
                        $budgetCategories[] = [
                            'label'   => $cat['label'],
                            'amount'  => $amount,
                            'planned' => $plannedByCategory[$cat['key']] ?? null,
                        ];
                    }
                }
                $onSite = $budget['details']['on-site'] ?? 0;
                if ($onSite > 0) {
                    $budgetCategories[] = [
                        'label'   => $t('trip.summary.category.on_site'),
                        'amount'  => round($onSite, 2),
                        'planned' => $plannedByCategory['on-site'] ?? null,
                    ];
                }

                $subject = $this->appName . $t('email.summary.subject');

                $email = (new TemplatedEmail())
                    ->from($this->fromMail)
                    ->to($user->getEmail())
                    ->subject($subject)
                    ->htmlTemplate('trip/summary-mail.html.twig')
                    ->context([
                        'trip' => $trip,
                        'user' => $user,
                        'nbActivities' => $trip->getActivities()->count(),
                        'budgetCategories' => $budgetCategories,
                        'totalPerPerson' => $totalPerPerson,
                        'nbTravelers' => $nbTravelers,
                        'budgetTotal' => $budget['total'],
                        'plannedTotal' => $plannedTotal > 0 ? $plannedTotal : null,
                        'domain' => $this->domain,
                        'app_name' => $this->appName,
                        'locale' => $locale,
                    ]);

                $this->mailer->send($email);
                $sent++;
            }
        }

        $io->success("Résumés envoyés : {$sent}");
        return Command::SUCCESS;
    }
}
