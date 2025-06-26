<?php

namespace App\Command;

use App\Entity\TripTraveler;
use App\Entity\User;
use App\Entity\UserBadges;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-ecussons',
    description: 'Regarde si l\'utilisateur a débloqué un nouvel écusson',
)]
class CheckEcussonsCommand extends Command
{
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct();
        $this->managerRegistry = $managerRegistry;
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
        /**
         * Objectif : voyages en solitude
         */
        $tripInSolo = $this->managerRegistry->getRepository(TripTraveler::class)->countTripInSolo($user);
        if ($tripInSolo) {
            $badgeData = [];

            switch ($tripInSolo) {
                case 1:
                    $badgeData = [
                        'title' => 'Premier voyage en solitaire',
                        'description' => 'Premiers pas vers l\'aventure en solo.',
                        'level' => 1
                    ];
                    break;
                case 5:
                    $badgeData = [
                        'title' => '5 voyages solo',
                        'description' => 'A l\'aise sans personne, même à l\'étranger.',
                        'level' => 2
                    ];
                    break;
                case 10:
                    $badgeData = [
                        'title' => '10 voyages solo',
                        'description' => 'Le monde est mieux sans guide.',
                        'level' => 3
                    ];
                    break;
                case 20:
                    $badgeData = [
                        'title' => '20 voyages solo',
                        'description' => 'Voyage seul, mais jamais perdu.',
                        'level' => 4
                    ];
                    break;
                case 50:
                    $badgeData = [
                        'title' => '50 voyages solo',
                        'description' => 'Libre comme l\'air, partout sur Terre.',
                        'level' => 5
                    ];
                    break;
            }

            if ($badgeData) {
                $badgeSolo = $this->managerRegistry->getRepository(UserBadges::class)->findOneBy(['name' => 'solo', 'user' => $user, 'level' => $badgeData['level']]);

                if (!$badgeSolo) {
                    $badgeSolo = (new UserBadges())
                        ->setName('solo')
                        ->setUser($user)
                        ->setTitle($badgeData['title'])
                        ->setDescription($badgeData['description'])
                        ->setLevel($badgeData['level']);

                    $this->managerRegistry->getManager()->persist($badgeSolo);
                }
            }
        }

        /**
         * Objectif : voyages à deux
         */
        $tripInDuo = $this->managerRegistry->getRepository(TripTraveler::class)->countTripInDuo($user);
        if ($tripInDuo) {
            $badgeData = [];

            switch ($tripInDuo) {
                case 1:
                    $badgeData = [
                        'title' => 'Premier voyage en duo',
                        'description' => 'Un premier voyage à deux, tout commence ici.',
                        'level' => 1
                    ];
                    break;
                case 5:
                    $badgeData = [
                        'title' => '5 voyages à deux',
                        'description' => 'Partir à deux devient une belle habitude.',
                        'level' => 2
                    ];
                    break;
                case 10:
                    $badgeData = [
                        'title' => '10 voyages à deux',
                        'description' => 'Le duo avance, main dans la main.',
                        'level' => 3
                    ];
                    break;
                case 20:
                    $badgeData = [
                        'title' => '20 voyages à deux',
                        'description' => 'Deux personnes, mille destinations, une même envie d\'ailleurs.',
                        'level' => 2
                    ];
                    break;
                case 50:
                    $badgeData = [
                        'title' => '50 voyages à deux',
                        'description' => 'L\'évasion, c\'est toujours mieux à deux.',
                        'level' => 2
                    ];
                    break;
            }

            if ($badgeData) {
                $badgeDuo = $this->managerRegistry->getRepository(UserBadges::class)->findOneBy(['name' => 'duo', 'user' => $user, 'level' => $badgeData['level']]);

                if (!$badgeDuo) {
                    $badgeDuo = (new UserBadges())
                        ->setName('duo')
                        ->setUser($user)
                        ->setTitle($badgeData['title'])
                        ->setDescription($badgeData['description'])
                        ->setLevel($badgeData['level']);

                    $this->managerRegistry->getManager()->persist($badgeDuo);
                }
            }
        }

        /**
         * Objectif : voyages en groupe (3+)
         */
        $tripInGroup = $this->managerRegistry->getRepository(TripTraveler::class)->countTripInGroup($user);
        if ($tripInGroup) {
            $badgeData = [];

            switch ($tripInGroup) {
                case 1:
                    $badgeData = [
                        'title' => 'Premier voyage en groupe',
                        'description' => 'Un premier trip collectif, et personne ne s’est perdu ? Bravo.',
                        'level' => 1
                    ];
                    break;
                case 5:
                    $badgeData = [
                        'title' => '5 voyages en groupe',
                        'description' => 'Déjà plusieurs trajets partagés. Tu aimes voyager entouré.',
                        'level' => 2
                    ];
                    break;
                case 10:
                    $badgeData = [
                        'title' => '10 voyages en groupe',
                        'description' => 'Tu sais te fondre dans un groupe sans perdre ton sac à dos.',
                        'level' => 3
                    ];
                    break;
                case 20:
                    $badgeData = [
                        'title' => '20 voyages en groupe',
                        'description' => 'Le cœur du groupe, c’est toi. Aucun trip sans ton nom dans la liste.',
                        'level' => 4
                    ];
                    break;
                case 50:
                    $badgeData = [
                        'title' => '50 voyages en groupe',
                        'description' => 'Sans toi, les voyages de groupe n’auraient pas la même saveur.',
                        'level' => 5
                    ];
                    break;
            }

            if ($badgeData) {
                $badgeGroup = $this->managerRegistry->getRepository(UserBadges::class)->findOneBy(['name' => 'group', 'user' => $user, 'level' => $badgeData['level']]);

                if (!$badgeGroup) {
                    $badgeGroup = (new UserBadges())
                        ->setName('group')
                        ->setUser($user)
                        ->setTitle($badgeData['title'])
                        ->setDescription($badgeData['description'])
                        ->setLevel($badgeData['level']);

                    $this->managerRegistry->getManager()->persist($badgeGroup);
                }
            }
        }

        /**
         * Objectif : voyages autour du monde
         */
        $countVisitedCountries = $this->managerRegistry->getRepository(TripTraveler::class)->countVisitedCountries($user);
        if ($countVisitedCountries) {
            $badgeData = [];
            switch ($countVisitedCountries) {
                case $countVisitedCountries < 5:
                    $badgeData = [
                        'title' => 'Premier pays visité',
                        'description' => 'Tu viens d\'ouvrir ton passeport au monde.',
                        'level' => 1
                    ];
                    break;
                case $countVisitedCountries >= 5 && $countVisitedCountries < 10:
                    $badgeData = [
                        'title' => '5 pays visités',
                        'description' => 'Ton passeport commence à noircir, et ta soif de découverte grandit.',
                        'level' => 2
                    ];
                    break;
                case $countVisitedCountries >= 10 && $countVisitedCountries < 20:
                    $badgeData = [
                        'title' => '10 pays visités',
                        'description' => 'Tu explores le monde avec aisance.',
                        'level' => 3
                    ];
                    break;
                case $countVisitedCountries >= 20:
                    $badgeData = [
                        'title' => 'Plus de 20 pays visités',
                        'description' => 'Le monde n\'a plus de secret pour toi.',
                        'level' => 4
                    ];
                    break;
            }

            if ($badgeData) {
                $badgeCountry = $this->managerRegistry->getRepository(UserBadges::class)->findOneBy(['name' => 'monde', 'user' => $user, 'level' => $badgeData['level']]);

                if (!$badgeCountry) {
                    $badgeCountry = (new UserBadges())
                        ->setName('monde')
                        ->setUser($user)
                        ->setTitle($badgeData['title'])
                        ->setDescription($badgeData['description'])
                        ->setLevel($badgeData['level']);

                    $this->managerRegistry->getManager()->persist($badgeCountry);
                }
            }
        }
    }
}
