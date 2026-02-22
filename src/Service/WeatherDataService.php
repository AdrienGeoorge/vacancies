<?php
// src/Service/WeatherDataService.php

namespace App\Service;

use App\Entity\ClimateData;
use App\Repository\ClimateDataRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherDataService
{
    public function __construct(
        private readonly ManagerRegistry       $managerRegistry,
        private readonly ClimateDataRepository $climateRepo,
        private readonly HttpClientInterface   $httpClient,
        private readonly LoggerInterface       $logger
    )
    {
    }

    /**
     * Stratégie intelligente :
     * 1. Chercher en BDD (capitales + villes déjà cherchées)
     * 2. Si absent : Open-Meteo + sauvegarde automatique en BDD
     * @throws \Exception
     */
    public function getWeatherForCity(string $city, ?string $country, int $month): array
    {
        // Chercher en BDD
        $climateData = $this->climateRepo->findByCityAndMonth($city, $month, $country);
        $needsUpdate = null;

        if ($climateData) {
            $this->logger->info("Météo trouvée en BDD", [
                'city' => $city,
                'country' => $country,
                'month' => $month,
                'source' => $climateData->getSource()
            ]);

            $daysSinceUpdate = (new \DateTime())->diff($climateData->getLastUpdated())->days;

            // Si > 5 ans (1825 jours), mettre à jour
            if ($daysSinceUpdate > 1825) {
                $this->logger->info("Données météo pour {$city} périmée (sauvegardé il y a {$daysSinceUpdate} jours), mise à jour...");
                $needsUpdate = $climateData;
            } else {
                return $climateData->toArray();
            }
        }

        // Pas en BDD : appeler Open-Meteo et sauvegarder
        $this->logger->info("Météo non trouvée ou périmée, appel Open-Meteo", [
            'city' => $city,
            'country' => $country
        ]);

        return $this->fetchAndStoreFromOpenMeteo($city, $country, $month, $needsUpdate);
    }

    private function fetchAndStoreFromOpenMeteo(string $city, ?string $country, int $month, ?ClimateData $needsUpdate): array
    {
        try {
            // Étape 1 : Géocoder la ville
            $coords = $this->geocodeCity($city, $country);

            if (!$coords) {
                $this->logger->warning("Géocodage échoué", ['city' => $city, 'country' => $country]);
                return ['error' => true, 'message' => 'Ville introuvable'];
            }

            if (null === $needsUpdate) {
                // Étape 2 : Vérifier si une ville proche existe déjà en BDD si pas en UPDATE
                $nearbyCity = $this->climateRepo->findNearbyCity($coords['lat'], $coords['lon'], $month, 50);

                if ($nearbyCity) {
                    $this->logger->info("Ville proche trouvée en BDD", [
                        'searched' => $city,
                        'found' => $nearbyCity->getCity(),
                        'distance' => '< 50km'
                    ]);

                    $lastUpdated = new \DateTime($nearbyCity->getLastUpdated());
                    $daysSinceUpdate = (new \DateTime())->diff($lastUpdated)->days;

                    // Si > 5 ans (1825 jours), mettre à jour
                    if ($daysSinceUpdate > 1825) {
                        $this->logger->info("Données météo pour {$city} périmée (sauvegardé il y a {$daysSinceUpdate} jours), mise à jour...");
                    } else {
                        return $nearbyCity->toArray();
                    }
                }
            }

            // Étape 3 : Récupérer les données climatiques depuis Open-Meteo
            $weatherData = $this->fetchFromOpenMeteo($coords['lat'], $coords['lon'], $month);

            if (isset($weatherData['error'])) {
                return $weatherData;
            }

            // Étape 4 : Sauvegarder en BDD pour les prochaines fois
            $this->saveClimateData($city, $country, $month, $weatherData, $coords, $needsUpdate);

            $this->logger->info("💾 Données Open-Meteo sauvegardées en BDD", [
                'city' => $city,
                'country' => $country
            ]);

            return $weatherData;

        } catch (\Exception $e) {
            $this->logger->error("Erreur Open-Meteo", [
                'city' => $city,
                'error' => $e->getMessage()
            ]);

            return ['error' => true, 'message' => 'Données météo indisponibles'];
        }
    }

    public function geocodeCity(string $city, ?string $country): ?array
    {
        try {
            $query = $city . ($country ? ", $country" : "");

            $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'addressdetails' => 1
                ],
                'headers' => [
                    'User-Agent' => 'TravelPlannerApp/1.0'
                ]
            ]);

            $data = $response->toArray();

            if (empty($data)) {
                return null;
            }

            return [
                'lat' => (float)$data[0]['lat'],
                'lon' => (float)$data[0]['lon'],
                'display_name' => $data[0]['display_name']
            ];

        } catch (\Exception $e) {
            $this->logger->error("Erreur géocodage", ['city' => $city, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function fetchFromOpenMeteo(float $lat, float $lon, int $month): array
    {
        try {
            $startYear = date('Y') - 11;
            $endYear = date('Y') - 1;

            $allTempsMin = [];
            $allTempsMax = [];
            $allPrecip = [];

            // Récupérer 10 ans de données
            for ($year = $startYear; $year <= $endYear; $year++) {
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                $startDate = sprintf('%04d-%02d-01', $year, $month);
                $endDate = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

                $response = $this->httpClient->request('GET', 'https://archive-api.open-meteo.com/v1/archive', [
                    'query' => [
                        'latitude' => $lat,
                        'longitude' => $lon,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'daily' => 'temperature_2m_min,temperature_2m_max,precipitation_sum',
                        'timezone' => 'auto'
                    ],
                    'timeout' => 15
                ]);

                $data = $response->toArray();

                if (isset($data['daily'])) {
                    $allTempsMin = array_merge($allTempsMin, $data['daily']['temperature_2m_min'] ?? []);
                    $allTempsMax = array_merge($allTempsMax, $data['daily']['temperature_2m_max'] ?? []);
                    $allPrecip = array_merge($allPrecip, $data['daily']['precipitation_sum'] ?? []);
                }

                usleep(100000); // 100ms pause
            }

            if (empty($allTempsMin)) {
                return ['error' => true, 'message' => 'Aucune donnée disponible'];
            }

            // Calculer les moyennes
            $tempMin = round(array_sum($allTempsMin) / count($allTempsMin), 1);
            $tempMax = round(array_sum($allTempsMax) / count($allTempsMax), 1);
            $avgPrecipPerDay = array_sum($allPrecip) / count($allPrecip);
            $totalPrecipMonth = round($avgPrecipPerDay * 30, 1);
            $rainyDays = round(count(array_filter($allPrecip, fn($p) => $p > 2)) / 10, 0);

            return [
                'temp_min' => $tempMin,
                'temp_max' => $tempMax,
                'rainfall_mm' => $totalPrecipMonth,
                'rainfall_days' => $rainyDays,
                'humidity' => $this->estimateHumidity($tempMin, $tempMax, $totalPrecipMonth),
                'daylight_hours' => $this->calculateDaylightHours($lat, $month),
                'source' => 'OpenMeteo'
            ];

        } catch (\Exception $e) {
            $this->logger->error("Erreur Open-Meteo API", ['error' => $e->getMessage()]);
            return ['error' => true, 'message' => 'API indisponible'];
        }
    }

    private function saveClimateData(
        string       $city,
        ?string      $country,
        int          $month,
        array        $weatherData,
        array        $coords,
        ?ClimateData $needsUpdate
    ): void
    {
        if ($needsUpdate !== null) {
            $climateData = $needsUpdate;
        } else {
            $climateData = new ClimateData();
        }

        $climateData->setCity($city);
        $climateData->setCountry($country);
        $climateData->setMonth($month);
        $climateData->setTempMinAvg($weatherData['temp_min']);
        $climateData->setTempMaxAvg($weatherData['temp_max']);
        $climateData->setPrecipitationMm($weatherData['rainfall_mm']);
        $climateData->setRainyDays($weatherData['rainfall_days']);
        $climateData->setSunshineHours($weatherData['daylight_hours']);
        $climateData->setHumidityAvg($weatherData['humidity']);
        $climateData->setLatitude($coords['lat']);
        $climateData->setLongitude($coords['lon']);
        $climateData->setSource('OpenMeteo');
        $climateData->setLastUpdated(new \DateTime());

        $this->managerRegistry->getManager()->persist($climateData);
        $this->managerRegistry->getManager()->flush();
    }

    private function estimateHumidity(float $tempMin, float $tempMax, float $rainfall): int
    {
        $avgTemp = ($tempMin + $tempMax) / 2;
        $baseHumidity = 60;

        if ($rainfall > 100) $baseHumidity += 15;
        else if ($rainfall > 50) $baseHumidity += 10;

        if ($avgTemp > 25) $baseHumidity += 10;
        if ($avgTemp < 10) $baseHumidity -= 10;

        return max(30, min(95, $baseHumidity));
    }

    private function calculateDaylightHours(float $lat, int $month): float
    {
        $baseHours = 12;

        if ($lat > 0) {
            $variation = sin(($month - 3) * M_PI / 6) * 4 * abs($lat) / 90;
        } else {
            $variation = sin(($month - 9) * M_PI / 6) * 4 * abs($lat) / 90;
        }

        return round($baseHours + $variation, 1);
    }
}