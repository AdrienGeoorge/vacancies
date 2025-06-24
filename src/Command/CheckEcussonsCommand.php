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
        $this->addOption('username', null, InputOption::VALUE_NONE, 'Nom d\'utilisateur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('username')) {
            // ...
        } else {
            $users = $this->managerRegistry->getRepository(User::class)->findAll();

            foreach ($users as $user) {
                $tripInSolo = $this->managerRegistry->getRepository(TripTraveler::class)->countTripInSolo($user);

                if ($tripInSolo) {
                    $badgeSolo = $this->managerRegistry->getRepository(UserBadges::class)->findOneBy(['name' => 'solo', 'user' => $user]);

                    if (!$badgeSolo) {
                        $badgeSolo = (new UserBadges())
                            ->setName('solo')
                            ->setUser($user);
                    }

                    switch ($tripInSolo) {
                        case 1:
                            $badgeSolo
                                ->setTitle('Explorateur discret')
                                ->setDescription('Premiers pas vers l\'aventure en solo.')
                                ->setLevel(1);
                            break;
                        case 5:
                            $badgeSolo
                                ->setTitle('Touriste indépendant')
                                ->setDescription('A l\'aise sans personne, même à l\'étranger.')
                                ->setLevel(2);
                            break;
                        case 10:
                            $badgeSolo
                                ->setTitle('Aventurer solitaire')
                                ->setDescription('Le monde est mieux sans guide.')
                                ->setLevel(3);
                            break;
                        case 20:
                            $badgeSolo
                                ->setTitle('Loup solitaire')
                                ->setDescription('Voyage seul, mais jamais perdu.')
                                ->setLevel(4);
                            break;
                        case 50:
                            $badgeSolo
                                ->setTitle('Globe-trotter libre')
                                ->setDescription('Libre comme l\'air, partout sur Terre.')
                                ->setLevel(5);
                            break;
                    }

                    $this->managerRegistry->getManager()->persist($badgeSolo);
                }

                $tripInDuo = $this->managerRegistry->getRepository(TripTraveler::class)->countTripInDuo($user);

                if ($tripInDuo) {
                    $badgeDuo = $this->managerRegistry->getRepository(UserBadges::class)->findOneBy(['name' => 'duo', 'user' => $user]);

                    if (!$badgeDuo) {
                        $badgeDuo = (new UserBadges())
                            ->setName('duo')
                            ->setUser($user);
                    }

                    switch ($tripInDuo) {
                        case 1:
                            $badgeDuo
                                ->setTitle('Binôme naissant')
                                ->setDescription('Un premier voyage à deux, tout commence ici.')
                                ->setLevel(1);
                            break;
                        case 5:
                            $badgeDuo
                                ->setTitle('Complice d\'escapade')
                                ->setDescription('Partir à deux devient une belle habitude.')
                                ->setLevel(2);
                            break;
                        case 10:
                            $badgeDuo
                                ->setTitle('Partenaire de route')
                                ->setDescription('Le duo avance, main dans la main.')
                                ->setLevel(3);
                            break;
                        case 20:
                            $badgeDuo
                                ->setTitle('Duo vagabond')
                                ->setDescription('Deux personnes, mille destinations, une même envie d\'ailleurs.')
                                ->setLevel(4);
                            break;
                        case 50:
                            $badgeDuo
                                ->setTitle('Compagnon d\'évasion')
                                ->setDescription('L\'évasion, c\'est toujours mieux à deux.')
                                ->setLevel(5);
                            break;
                    }

                    $this->managerRegistry->getManager()->persist($badgeDuo);
                }
            }

            $this->managerRegistry->getManager()->flush();
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
