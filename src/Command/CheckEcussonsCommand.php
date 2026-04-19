<?php

namespace App\Command;

use App\Entity\TripTraveler;
use App\Entity\User;
use App\Entity\UserBadges;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:check-ecussons',
    description: 'Regarde si l\'utilisateur a débloqué un nouvel écusson',
)]
class CheckEcussonsCommand extends Command
{
    private ManagerRegistry $managerRegistry;
    private TranslatorInterface $translator;

    public function __construct(ManagerRegistry $managerRegistry, TranslatorInterface $translator)
    {
        parent::__construct();
        $this->managerRegistry = $managerRegistry;
        $this->translator = $translator;
    }

    protected function configure(): void
    {
        $this->addOption('username', null, InputOption::VALUE_OPTIONAL, 'Nom d\'utilisateur');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('username')) {
            $io->info('Début du traitement de distribution des écussons pour ' . $input->getOption('username'));

            $user = $this->managerRegistry->getRepository(User::class)->findOneBy(['username' => $input->getOption('username')]);
            $this->treatmentByUser($user);
        } else {
            $io->info('Début du traitement journalier de distribution des écussons');

            $users = $this->managerRegistry->getRepository(User::class)->findAll();
            foreach ($users as $user) $this->treatmentByUser($user);
        }

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
        $locale = $user->getLanguage() ?? 'fr';
        $t = fn(string $key) => $this->translator->trans($key, [], 'messages', $locale);

        /**
         * Objectif : nombre de pays visités
         */
        $countVisitedCountries = $this->managerRegistry->getRepository(TripTraveler::class)->countVisitedCountries($user);
        if ($countVisitedCountries) {
            $badgeData = ['name' => 'monde'];

            switch ($countVisitedCountries) {
                case $countVisitedCountries < 5:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.monde.1.title'),
                        'description' => $t('badge.monde.1.description'),
                        'level' => 1
                    ]);
                    break;
                case $countVisitedCountries >= 5 && $countVisitedCountries < 10:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.monde.2.title'),
                        'description' => $t('badge.monde.2.description'),
                        'level' => 2
                    ]);
                    break;
                case $countVisitedCountries >= 10 && $countVisitedCountries < 20:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.monde.3.title'),
                        'description' => $t('badge.monde.3.description'),
                        'level' => 3
                    ]);
                    break;
                case $countVisitedCountries >= 20:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.monde.5.title'),
                        'description' => $t('badge.monde.5.description'),
                        'level' => 5
                    ]);
                    break;
            }

            $this->addBadge($user, $badgeData);
        }

        $visitedContinents = $this->managerRegistry->getRepository(TripTraveler::class)->getVisitedContinents($user);
        if (count($visitedContinents) === 3) {
            $this->addBadge($user, [
                'name' => 'monde',
                'title' => $t('badge.monde.4.title'),
                'description' => $t('badge.monde.4.description'),
                'level' => 4
            ]);
        }

        $requiredContinents = ['Europe', 'Asie', 'Amérique', 'Afrique', 'Océanie'];
        $hasDoneWorldTour = count(array_intersect($visitedContinents, $requiredContinents)) === count($requiredContinents);
        if ($hasDoneWorldTour) {
            $this->addBadge($user, [
                'name' => 'monde',
                'title' => $t('badge.monde.6.title'),
                'description' => $t('badge.monde.6.description'),
                'level' => 6
            ]);
        }

        /**
         * Objectif : voyages en solitude
         */
        $tripInSolo = $this->managerRegistry->getRepository(TripTraveler::class)->countTripInSolo($user);
        if ($tripInSolo) {
            $badgeData = ['name' => 'solo'];

            switch ($tripInSolo) {
                case $tripInSolo < 5:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.solo.1.title'),
                        'description' => $t('badge.solo.1.description'),
                        'level' => 1
                    ]);
                    break;
                case $tripInSolo >= 5 && $tripInSolo < 10:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.solo.2.title'),
                        'description' => $t('badge.solo.2.description'),
                        'level' => 2
                    ]);
                    break;
                case $tripInSolo >= 10 && $tripInSolo < 20:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.solo.3.title'),
                        'description' => $t('badge.solo.3.description'),
                        'level' => 3
                    ]);
                    break;
                case $tripInSolo >= 20 && $tripInSolo < 50:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.solo.4.title'),
                        'description' => $t('badge.solo.4.description'),
                        'level' => 4
                    ]);
                    break;
                case $tripInSolo >= 50:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.solo.5.title'),
                        'description' => $t('badge.solo.5.description'),
                        'level' => 5
                    ]);
                    break;
            }

            $this->addBadge($user, $badgeData);
        }

        /**
         * Objectif : voyages à deux
         */
        $tripInDuo = $this->managerRegistry->getRepository(TripTraveler::class)->countTripInDuo($user);
        if ($tripInDuo) {
            $badgeData = ['name' => 'duo'];

            switch ($tripInDuo) {
                case $tripInDuo < 5:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.duo.1.title'),
                        'description' => $t('badge.duo.1.description'),
                        'level' => 1
                    ]);
                    break;
                case $tripInDuo >= 5 && $tripInDuo < 10:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.duo.2.title'),
                        'description' => $t('badge.duo.2.description'),
                        'level' => 2
                    ]);
                    break;
                case $tripInDuo >= 10 && $tripInDuo < 20:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.duo.3.title'),
                        'description' => $t('badge.duo.3.description'),
                        'level' => 3
                    ]);
                    break;
                case $tripInDuo >= 20 && $tripInDuo < 50:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.duo.4.title'),
                        'description' => $t('badge.duo.4.description'),
                        'level' => 4
                    ]);
                    break;
                case $tripInDuo >= 50:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.duo.5.title'),
                        'description' => $t('badge.duo.5.description'),
                        'level' => 5
                    ]);
                    break;
            }

            $this->addBadge($user, $badgeData);
        }

        /**
         * Objectif : voyages en groupe (3+)
         */
        $tripInGroup = $this->managerRegistry->getRepository(TripTraveler::class)->countTripInGroup($user);
        if ($tripInGroup) {
            $badgeData = ['name' => 'group'];

            switch ($tripInGroup) {
                case $tripInGroup < 5:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.group.1.title'),
                        'description' => $t('badge.group.1.description'),
                        'level' => 1
                    ]);
                    break;
                case $tripInGroup >= 5 && $tripInGroup < 10:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.group.2.title'),
                        'description' => $t('badge.group.2.description'),
                        'level' => 2
                    ]);
                    break;
                case $tripInGroup >= 10 && $tripInGroup < 20:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.group.3.title'),
                        'description' => $t('badge.group.3.description'),
                        'level' => 3
                    ]);
                    break;
                case $tripInGroup >= 20 && $tripInGroup < 50:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.group.4.title'),
                        'description' => $t('badge.group.4.description'),
                        'level' => 4
                    ]);
                    break;
                case $tripInGroup >= 50:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.group.5.title'),
                        'description' => $t('badge.group.5.description'),
                        'level' => 5
                    ]);
                    break;
            }

            $this->addBadge($user, $badgeData);
        }

        /**
         * Objectif : styles de voyages
         */
        $countMuseum = $this->managerRegistry->getRepository(TripTraveler::class)->countMuseum($user);
        if ($countMuseum) {
            $badgeData = ['name' => 'museum'];

            switch ($countMuseum['nbMuseums']) {
                case $countMuseum['nbMuseums'] < 10:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.museum.1.title'),
                        'description' => $t('badge.museum.1.description'),
                        'level' => 1
                    ]);
                    break;
                case $countMuseum['nbMuseums'] >= 10:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.museum.2.title'),
                        'description' => $t('badge.museum.2.description'),
                        'level' => 2
                    ]);
                    break;
            }

            $this->addBadge($user, $badgeData);
        }

        $countParks = $this->managerRegistry->getRepository(TripTraveler::class)->countAmusementPark($user);
        if ($countParks) {
            $badgeData = ['name' => 'park'];

            switch ($countMuseum['nbParks']) {
                case $countMuseum['nbParks'] < 10:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.park.1.title'),
                        'description' => $t('badge.park.1.description'),
                        'level' => 1
                    ]);
                    break;
                case $countMuseum['nbParks'] >= 10:
                    $badgeData = array_merge($badgeData, [
                        'title' => $t('badge.park.2.title'),
                        'description' => $t('badge.park.2.description'),
                        'level' => 2
                    ]);
                    break;
            }

            $this->addBadge($user, $badgeData);
        }
    }

    private function addBadge(User $user, array $data): void
    {
        if (count($data) == 4) {
            $badge = $this->managerRegistry->getRepository(UserBadges::class)->findOneBy(['name' => $data['name'], 'user' => $user, 'level' => $data['level']]);

            if (!$badge) {
                $badge = (new UserBadges())
                    ->setName($data['name'])
                    ->setUser($user)
                    ->setTitle($data['title'])
                    ->setDescription($data['description'])
                    ->setLevel($data['level'])
                    ->setReceivedAt(new \DateTime());

                $this->managerRegistry->getManager()->persist($badge);
            }
        }
    }
}
