<?php

namespace App\Command;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Repository\CountryRepository;
use App\Entity\ClimateData;

class ImportClimateDataCommand extends Command
{
    protected static $defaultName = 'app:import-climate-data';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ManagerRegistry     $managerRegistry,
        private readonly CountryRepository   $countryRepository,
        private readonly string              $weatherApiKey
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Importe les données climatiques moyennes sur 10 ans pour toutes les capitales')
            ->addArgument('city', InputArgument::OPTIONAL, 'Ville spécifique à importer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import des données climatiques sur 10 ans');

        $cityFilter = $input->getArgument('city');
        if ($cityFilter) {
            $this->importCityData($cityFilter);
            $io->success("Import terminé pour {$cityFilter}");
            return Command::SUCCESS;
        }

        $countries = $this->countryRepository->findAll();
        $io->progressStart(count($countries));

        foreach ($countries as $country) {
            $capital = $country->getCapital();

            if (!$capital) {
                $io->warning("Pas de capitale pour {$country->getName()}");
                $io->progressAdvance();
                continue;
            }

            try {
                $this->importCityData($capital);
                $io->progressAdvance();

                // Pause pour respecter les limites de l'API
                usleep(500000); // 0.5 secondes
            } catch (\Exception $e) {
                $io->error("Erreur pour {$capital}: {$e->getMessage()}");
                $io->progressAdvance();
            }
        }

        $io->progressFinish();
        $io->success('Import terminé !');

        return Command::SUCCESS;
    }

    private function importCityData(string $city): void
    {
        // Pour chaque mois (1 à 12)
        for ($month = 1; $month <= 12; $month++) {
            $monthlyData = $this->getMonthlyAverageOver10Years($city, $month);

            // Créer ou mettre à jour l'entité
            $climateData = $this->managerRegistry->getRepository(ClimateData::class)
                ->findByCityAndMonth($city, $month);

            if (!$climateData) {
                $climateData = new ClimateData();
                $climateData->setCity($city);
                $climateData->setMonth($month);
            }

            $climateData->setTempMinAvg($monthlyData['temp_min']);
            $climateData->setTempMaxAvg($monthlyData['temp_max']);
            $climateData->setPrecipitationMm($monthlyData['precipitation']);
            $climateData->setRainyDays($monthlyData['rainy_days']);
            $climateData->setSunshineHours($monthlyData['daylight_hours']); // ✅ Renommé
            $climateData->setHumidityAvg($monthlyData['humidity']);
            $climateData->setSource('WeatherAPI - Moyenne 10 ans');
            $climateData->setLastUpdated(new \DateTime());

            $this->managerRegistry->getManager()->persist($climateData);
            $this->managerRegistry->getManager()->flush();
        }
    }

    private function getMonthlyAverageOver10Years(string $city, int $month): array
    {
        $allTempsMin = [];
        $allTempsMax = [];
        $allPrecipitations = [];
        $allHumidities = [];
        $allDaylightHours = []; // ✅ Nouveau
        $rainyDaysCount = 0;
        $totalDays = 0;

        $currentYear = (int)date('Y');

        for ($yearsAgo = 1; $yearsAgo <= 10; $yearsAgo++) {
            $year = $currentYear - $yearsAgo;
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

            try {
                $response = $this->httpClient->request('GET', 'https://api.weatherapi.com/v1/history.json', [
                    'query' => [
                        'key' => $this->weatherApiKey,
                        'q' => $city,
                        'dt' => $startDate,
                        'end_dt' => $endDate,
                        'lang' => 'fr',
                    ],
                    'timeout' => 30,
                ]);

                $data = $response->toArray();

                if (!isset($data['forecast']['forecastday'])) {
                    error_log("❌ Pas de données pour {$city} {$year}-{$month}");
                    continue;
                }

                $daysReceived = count($data['forecast']['forecastday']);

                foreach ($data['forecast']['forecastday'] as $day) {
                    $dayData = $day['day'];

                    $allTempsMin[] = $dayData['mintemp_c'];
                    $allTempsMax[] = $dayData['maxtemp_c'];
                    $allPrecipitations[] = $dayData['totalprecip_mm'] ?? 0;
                    $allHumidities[] = $dayData['avghumidity'];

                    // ✅ Calculer les heures de jour depuis les données astro
                    if (isset($day['astro']['sunrise']) && isset($day['astro']['sunset'])) {
                        $daylight = $this->calculateDaylightFromAstro(
                            $day['astro']['sunrise'],
                            $day['astro']['sunset']
                        );
                        if ($daylight > 0) {
                            $allDaylightHours[] = $daylight;
                        }
                    }

                    if (($dayData['totalprecip_mm'] ?? 0) > 2) {
                        $rainyDaysCount++;
                    }

                    $totalDays++;
                }

                usleep(300000);
            } catch (\Exception $e) {
                error_log("❌ Erreur pour {$city} {$year}-{$month}: {$e->getMessage()}");
            }
        }

        if (empty($allTempsMin)) {
            throw new \Exception("Aucune donnée récupérée pour {$city} mois {$month}");
        }

        // Calculer les moyennes
        $tempMin = round(array_sum($allTempsMin) / count($allTempsMin), 1);
        $tempMax = round(array_sum($allTempsMax) / count($allTempsMax), 1);
        $avgPrecipPerDay = array_sum($allPrecipitations) / count($allPrecipitations);
        $totalPrecipMonth = round($avgPrecipPerDay * 30, 1);
        $rainyDaysPerMonth = round(($rainyDaysCount / $totalDays) * 30);
        $avgHumidity = round(array_sum($allHumidities) / count($allHumidities));

        // ✅ Moyenne des heures de jour (ou fallback si pas de données astro)
        $avgDaylightHours = !empty($allDaylightHours)
            ? round(array_sum($allDaylightHours) / count($allDaylightHours), 1)
            : $this->getFallbackDaylightHours($month);

        // Coefficient correctif humidité pour les mois de mousson
        if (in_array($month, [6, 7, 8, 9]) && $avgHumidity < 70) {
            $avgHumidity = min(95, $avgHumidity + 15);
        }

        return [
            'temp_min' => $tempMin,
            'temp_max' => $tempMax,
            'precipitation' => $totalPrecipMonth,
            'rainy_days' => $rainyDaysPerMonth,
            'daylight_hours' => $avgDaylightHours,
            'humidity' => $avgHumidity,
        ];
    }

    // ✅ Nouvelle fonction : Calculer les heures de jour depuis sunrise/sunset
    private function calculateDaylightFromAstro(string $sunrise, string $sunset): float
    {
        $sunriseTime = \DateTime::createFromFormat('h:i A', $sunrise);
        $sunsetTime = \DateTime::createFromFormat('h:i A', $sunset);

        if (!$sunriseTime || !$sunsetTime) {
            return 0;
        }

        $diff = $sunsetTime->diff($sunriseTime);
        return $diff->h + ($diff->i / 60);
    }

    // ✅ Fallback si pas de données astro
    private function getFallbackDaylightHours(int $month): float
    {
        // Heures de jour approximatives pour latitude ~45°N (moyenne mondiale)
        $daylightByMonth = [
            1 => 9.0, 2 => 10.5, 3 => 12.0, 4 => 13.5, 5 => 15.0, 6 => 16.0,
            7 => 15.5, 8 => 14.5, 9 => 13.0, 10 => 11.5, 11 => 10.0, 12 => 8.5
        ];

        return $daylightByMonth[$month] ?? 12.0;
    }
}