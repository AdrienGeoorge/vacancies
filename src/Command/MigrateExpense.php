<?php

namespace App\Command;

use App\Entity\Accommodation;
use App\Entity\AccommodationAdditional;
use App\Entity\Activity;
use App\Entity\Currency;
use App\Entity\OnSiteExpense;
use App\Entity\Transport;
use App\Entity\VariousExpensive;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-expense',
    description: 'Euro par défaut sur les dépenses déjà existantes.',
)]
class MigrateExpense extends Command
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $progressBar = new ProgressBar($output, 100);

        $eurCurrency = $this->managerRegistry
            ->getRepository(Currency::class)
            ->find('EUR');

        if (!$eurCurrency) {
            $io->error('Devise EUR non trouvée dans la table currency !');
            return Command::FAILURE;
        }

        $accommodations = $this->managerRegistry->getRepository(Accommodation::class)->findBy([
            'originalPrice' => null
        ]);

        $additionalAccommodations = $this->managerRegistry->getRepository(AccommodationAdditional::class)->findBy([
            'originalPrice' => null
        ]);

        $activities = $this->managerRegistry->getRepository(Activity::class)->findBy([
            'originalPrice' => null
        ]);

        $onSiteExpenses = $this->managerRegistry->getRepository(OnSiteExpense::class)->findBy([
            'originalPrice' => null
        ]);

        $transports = $this->managerRegistry->getRepository(Transport::class)->findBy([
            'originalPrice' => null
        ]);

        $variousExpenses = $this->managerRegistry->getRepository(VariousExpensive::class)->findBy([
            'originalPrice' => null
        ]);

        $total = count($accommodations) + count($additionalAccommodations) + count($activities) + count($onSiteExpenses) + count($transports) + count($variousExpenses);

        if ($total === 0) {
            $io->success('Aucune dépense à migrer !');
            return Command::SUCCESS;
        }

        $io->info("$total dépenses à migrer...");
        $progressBar->start($total);

        $this->setAmountAndCurrency($accommodations, $eurCurrency, $progressBar);
        $this->setAmountAndCurrency($additionalAccommodations, $eurCurrency, $progressBar);
        $this->setAmountAndCurrency($activities, $eurCurrency, $progressBar);
        $this->setAmountAndCurrency($onSiteExpenses, $eurCurrency, $progressBar);
        $this->setAmountAndCurrency($transports, $eurCurrency, $progressBar);
        $this->setAmountAndCurrency($variousExpenses, $eurCurrency, $progressBar);

        $this->managerRegistry->getManager()->flush();
        $progressBar->finish();

        $io->success("$total dépenses migrées avec succès !");

        return Command::SUCCESS;
    }

    /**
     * @param array $expenses
     * @param Currency $eurCurrency
     * @param ProgressBar $progressBar
     * @return void
     */
    protected function setAmountAndCurrency(array $expenses, Currency $eurCurrency, ProgressBar $progressBar): void
    {
        foreach ($expenses as $expense) {
            $expense->setOriginalPrice($expense->getPrice());
            $expense->setOriginalCurrency($eurCurrency);

            $this->managerRegistry->getManager()->persist($expense);
            $progressBar->advance();
        }
    }
}
