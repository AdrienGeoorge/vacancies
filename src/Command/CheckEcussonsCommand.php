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
         * Objectif : nombre de pays visités
         */
        $countVisitedCountries = $this->managerRegistry->getRepository(TripTraveler::class)->countVisitedCountries($user);
        if ($countVisitedCountries) {
            $badgeData = ['name' => 'monde'];

            switch ($countVisitedCountries) {
                case $countVisitedCountries < 5:
                    $badgeData = array_merge($badgeData, [
                        'title' => 'Premier pays visité',
                        'description' => 'Tu viens d\'ouvrir ton passeport au monde.',
                        'level' => 1
                    ]);
                    break;
                case $countVisitedCountries >= 5 && $countVisitedCountries < 10:
                    $badgeData = array_merge($badgeData, [
                        'title' => '5 pays visités',
                        'description' => 'Ton passeport commence à noircir, et ta soif de découverte grandit.',
                        'level' => 2
                    ]);
                    break;
                case $countVisitedCountries >= 10 && $countVisitedCountries < 20:
                    $badgeData = array_merge($badgeData, [
                        'title' => '10 pays visités',
                        'description' => 'Tu explores le monde avec aisance.',
                        'level' => 3
                    ]);
                    break;
                case $countVisitedCountries >= 20:
                    $badgeData = array_merge($badgeData, [
                        'title' => 'Plus de 20 pays visités',
                        'description' => 'Le monde n\'a plus de secret pour toi.',
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
                'title' => 'Explorateur de continents',
                'description' => 'Tu as foulé le sol de trois continents différents.',
                'level' => 4
            ]);
        }

        $requiredContinents = ['Europe', 'Asie', 'Amérique', 'Afrique', 'Océanie'];
        $hasDoneWorldTour = count(array_intersect($visitedContinents, $requiredContinents)) === count($requiredContinents);
        if ($hasDoneWorldTour) {
            $this->addBadge($user, [
                'name' => 'monde',
                'title' => 'Tour du monde',
                'description' => 'Tu as visité au moins un pays sur chaque continent habité. Un vrai globe-trotteur!',
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
                        'title' => 'Premier voyage en solitaire',
                        'description' => 'Premiers pas vers l\'aventure en solo.',
                        'level' => 1
                    ]);
                    break;
                case $tripInSolo >= 5 && $tripInSolo < 10:
                    $badgeData = array_merge($badgeData, [
                        'title' => '5 voyages solo',
                        'description' => 'A l\'aise sans personne, même à l\'étranger.',
                        'level' => 2
                    ]);
                    break;
                case $tripInSolo >= 10 && $tripInSolo < 20:
                    $badgeData = array_merge($badgeData, [
                        'title' => '10 voyages solo',
                        'description' => 'Le monde est mieux sans guide.',
                        'level' => 3
                    ]);
                    break;
                case $tripInSolo >= 20 && $tripInSolo < 50:
                    $badgeData = array_merge($badgeData, [
                        'title' => '20 voyages solo',
                        'description' => 'Voyage seul, mais jamais perdu.',
                        'level' => 4
                    ]);
                    break;
                case $tripInSolo >= 50:
                    $badgeData = array_merge($badgeData, [
                        'title' => '50 voyages solo',
                        'description' => 'Libre comme l\'air, partout sur Terre.',
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
                        'title' => 'Premier voyage en duo',
                        'description' => 'Un premier voyage à deux, tout commence ici.',
                        'level' => 1
                    ]);
                    break;
                case $tripInDuo >= 5 && $tripInDuo < 10:
                    $badgeData = array_merge($badgeData, [
                        'title' => '5 voyages à deux',
                        'description' => 'Partir à deux devient une belle habitude.',
                        'level' => 2
                    ]);
                    break;
                case $tripInDuo >= 10 && $tripInDuo < 20:
                    $badgeData = array_merge($badgeData, [
                        'title' => '10 voyages à deux',
                        'description' => 'Le duo avance, main dans la main.',
                        'level' => 3
                    ]);
                    break;
                case $tripInDuo >= 20 && $tripInDuo < 50:
                    $badgeData = array_merge($badgeData, [
                        'title' => '20 voyages à deux',
                        'description' => 'Deux personnes, mille destinations, une même envie d\'ailleurs.',
                        'level' => 2
                    ]);
                    break;
                case $tripInDuo >= 50:
                    $badgeData = array_merge($badgeData, [
                        'title' => '50 voyages à deux',
                        'description' => 'L\'évasion, c\'est toujours mieux à deux.',
                        'level' => 2
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
                        'title' => 'Premier voyage en groupe',
                        'description' => 'Un premier trip collectif, et personne ne s’est perdu ? Bravo.',
                        'level' => 1
                    ]);
                    break;
                case $tripInGroup >= 5 && $tripInGroup < 10:
                    $badgeData = array_merge($badgeData, [
                        'title' => '5 voyages en groupe',
                        'description' => 'Déjà plusieurs trajets partagés. Tu aimes voyager entouré.',
                        'level' => 2
                    ]);
                    break;
                case $tripInGroup >= 10 && $tripInGroup < 20:
                    $badgeData = array_merge($badgeData, [
                        'title' => '10 voyages en groupe',
                        'description' => 'Tu sais te fondre dans un groupe sans perdre ton sac à dos.',
                        'level' => 3
                    ]);
                    break;
                case $tripInGroup >= 20 && $tripInGroup < 50:
                    $badgeData = array_merge($badgeData, [
                        'title' => '20 voyages en groupe',
                        'description' => 'Le cœur du groupe, c’est toi. Aucun trip sans ton nom dans la liste.',
                        'level' => 4
                    ]);
                    break;
                case $tripInGroup >= 50:
                    $badgeData = array_merge($badgeData, [
                        'title' => '50 voyages en groupe',
                        'description' => 'Sans toi, les voyages de groupe n’auraient pas la même saveur.',
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
                        'title' => 'Historien urbain',
                        'description' => 'Apprenti historien en pleine découverte.',
                        'level' => 1
                    ]);
                    break;
                case $countMuseum['nbMuseums'] >= 10:
                    $badgeData = array_merge($badgeData, [
                        'title' => 'Ami des galeries',
                        'description' => 'Passion culturelle affirmée, curiosité insatiable.',
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
                        'title' => 'Amateur de sensation',
                        'description' => 'Premiers frissons, l’adrénaline monte doucement.',
                        'level' => 1
                    ]);
                    break;
                case $countMuseum['nbParks'] >= 10:
                    $badgeData = array_merge($badgeData, [
                        'title' => 'Légende du looping',
                        'description' => 'Maître des loopings et des descentes vertigineuses.',
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
