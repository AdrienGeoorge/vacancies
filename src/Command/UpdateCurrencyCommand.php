<?php

namespace App\Command;

use App\Entity\ExchangeRate;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:update-currency',
    description: 'Met à jour le taux de changes des devises par rapport à l\'euro.',
)]
class UpdateCurrencyCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry       $managerRegistry,
        private readonly HttpClientInterface   $httpClient,
        private readonly ParameterBagInterface $params,
    )
    {
        parent::__construct();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $apiKey = $this->params->get('exchangerate_api_key');
            $apiUrl = $this->params->get('exchangerate_api_url');
            $url = sprintf('%s/latest?access_key=%s&base=EUR', $apiUrl, $apiKey);

            $io->info('Récupération des taux depuis exchangeratesapi.io...');

            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();

            if ($data['success'] !== true) {
                throw new \Exception('Erreur API : ' . ($data['error-type'] ?? 'Unknown'));
            }

            $rates = $data['rates'];
            $lastUpdate = new \DateTime('@' . $data['timestamp']);

            $today = new \DateTime('today');
            $existingRate = $this->managerRegistry->getRepository(ExchangeRate::class)
                ->findOneBy(['date' => $today]);

            if (!$existingRate) {
                $exchangeRate = (new ExchangeRate())
                    ->setDate($lastUpdate)
                    ->setRates($rates)
                    ->setCreatedAt(new \DateTimeImmutable());

                $this->managerRegistry->getManager()->persist($exchangeRate);
            }

            $this->managerRegistry->getManager()->flush();

            $io->success(sprintf('Taux de change mis à jour pour %d devises', count($rates)));
            $io->info('Dernière mise à jour API : ' . $lastUpdate->format('d/m/Y H:i:s'));

            return Command::SUCCESS;
        } catch (\Exception|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            $io->error('Erreur lors de la mise à jour : ' . $e->getMessage());
            $output->writeln('<fg=red>Détails: ' . $e->getTraceAsString() . '</>');

            return Command::FAILURE;
        }
    }
}
