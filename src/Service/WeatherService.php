<?php

namespace App\Service;

use App\Entity\Trip;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class WeatherService
{
    private const API_BASE_URL = 'https://api.weatherapi.com/v1';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface     $logger,
        private readonly string              $weatherApiKey,
        private readonly WeatherDataService  $weatherDataService
    )
    {
    }

    public function getCities(Trip $trip): array
    {
        $cities = [];

        foreach ($trip->getAccommodations() as $accommodation) {
            $cities[] = [
                'name' => $accommodation?->getCity(),
                'country' => $accommodation?->getCountry(),
                'arrivalDate' => $accommodation?->getArrivalDate(),
                'departureDate' => $accommodation?->getDepartureDate(),
            ];
        }

        if (empty($cities)) {
            foreach ($trip->getDestinations() as $destination) {
                $cities[] = [
                    'name' => $destination->getCountry()?->getCapital(),
                    'country' => $destination->getCountry()?->getName(),
                    'arrivalDate' => $destination->getDepartureDate(),
                    'departureDate' => $destination->getReturnDate(),
                ];
            }
        }

        return $cities;
    }

    public function getWeatherByDestinations(array $cities, Trip $trip): array
    {
        $weatherByDestination = [];

        foreach ($cities as $city) {
            if (!$city['name']) continue;

            $weatherData = $this->getWeatherForTrip(
                $city['name'],
                $city['country'],
                $city['arrivalDate'] ?: $trip->getDepartureDate(),
                $city['departureDate'] ?: $trip->getReturnDate()
            );

            if ($weatherData) {
                $weatherByDestination[] = [
                    'destination' => [
                        'country' => $city['country'],
                        'city' => $city['name'],
                        'arrivalDate' => $city['arrivalDate']?->format('Y-m-d') ?: $trip->getDepartureDate()?->format('Y-m-d'),
                        'departureDate' => $city['departureDate']?->format('Y-m-d') ?: $trip->getReturnDate()?->format('Y-m-d'),
                    ],
                    'weather' => $weatherData
                ];
            }
        }

        return $weatherByDestination;
    }

    /**
     * Point d'entrée principal
     */
    public function getWeatherForTrip(
        string     $cityName,
        ?string    $country,
        ?\DateTime $departureDate,
        ?\DateTime $returnDate
    ): ?array
    {
        try {
            if (!$departureDate) return null;

            $daysUntilDeparture = (new \DateTime())->diff($departureDate)->days;

            if ($daysUntilDeparture < 4) {
                // Prévisions réelles, avec fallback sur les moyennes historiques
                try {
                    $forecast = $this->getRealForecast($cityName, $returnDate);
                    if (!empty($forecast['days'])) {
                        return $forecast;
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Prévisions réelles indisponibles, fallback sur moyennes historiques', [
                        'city' => $cityName,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Moyennes historiques
            return $this->getHistoricalAverages($cityName, $country, $departureDate, $returnDate);
        } catch (\Exception $e) {
            $this->logger->error('Erreur WeatherService', [
                'city' => $cityName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Prévisions réelles (< 4 jours)
     */
    private function getRealForecast(
        string    $cityName,
        \DateTime $returnDate
    ): array
    {
        $daysToFetch = min(3, (new \DateTime())->diff($returnDate)->days + 1);

        $response = $this->httpClient->request('GET', self::API_BASE_URL . '/forecast.json', [
            'query' => [
                'key' => $this->weatherApiKey,
                'q' => $cityName,
                'days' => $daysToFetch,
                'lang' => 'fr',
            ],
        ]);

        $data = $response->toArray();
        $forecastDays = $data['forecast']['forecastday'];

        return $this->analyzeWeatherData($forecastDays);
    }

    private function getHistoricalAverages(
        string     $cityName,
        ?string    $country,
        \DateTime  $departureDate,
        ?\DateTime $returnDate
    ): array
    {
        $tripDays = $departureDate->diff($returnDate)->days + 1;
        $monthsData = [];
        $currentDate = clone $departureDate;

        while ($currentDate <= $returnDate) {
            $month = (int)$currentDate->format('n');
            $year = (int)$currentDate->format('Y');

            if (!isset($monthsData[$month])) {
                $weatherData = $this->weatherDataService->getWeatherForCity(
                    $cityName,
                    $country,
                    $month
                );

                if (isset($weatherData['error'])) {
                    throw new \Exception("Pas de données pour {$cityName} mois {$month}");
                }

                // Compter combien de jours du voyage sont dans ce mois
                $monthStart = max($currentDate, new \DateTime("{$year}-{$month}-01"));
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                $monthEnd = min($returnDate, new \DateTime("{$year}-{$month}-{$daysInMonth}"));
                $daysInThisMonth = $monthStart->diff($monthEnd)->days + 1;

                $monthsData[$month] = [
                    'data' => $weatherData,
                    'days' => $daysInThisMonth,
                    'weight' => $daysInThisMonth / $tripDays
                ];
            }

            $currentDate->modify('+1 day');
        }

        if (count($monthsData) === 1) {
            $data = reset($monthsData)['data'];

            return [
                'temp_min' => (int)$data['temp_min'],
                'temp_max' => (int)$data['temp_max'],
                'rainfall_days' => (int)$data['rainfall_days'],
                'rainfall_mm' => (float)$data['rainfall_mm'],
                'daylight_hours' => (float)$data['daylight_hours'],
                'humidity' => (int)$data['humidity'],
                'advice' => $this->generateAdvice(
                    (int)$data['temp_min'],
                    (int)$data['temp_max'],
                    (int)$data['rainfall_days'],
                    (float)$data['rainfall_mm'],
                    (int)$data['humidity'],
                    ''
                ),
                'main_condition' => '',
                'is_forecast' => false,
                'source' => $data['source']
            ];
        }

        // Plusieurs mois : calculer les moyennes pondérées
        $weightedData = [
            'temp_min' => 0,
            'temp_max' => 0,
            'rainfall_days' => 0,
            'rainfall_mm' => 0,
            'daylight_hours' => 0,
            'humidity' => 0,
        ];

        foreach ($monthsData as $month => $info) {
            $data = $info['data'];
            $weight = $info['weight'];

            $weightedData['temp_min'] += $data['temp_min'] * $weight;
            $weightedData['temp_max'] += $data['temp_max'] * $weight;
            $weightedData['rainfall_days'] += $data['rainfall_days'] * $weight;
            $weightedData['rainfall_mm'] += $data['rainfall_mm'] * $weight;
            $weightedData['daylight_hours'] += $data['daylight_hours'] * $weight;
            $weightedData['humidity'] += $data['humidity'] * $weight;
        }

        return [
            'temp_min' => round($weightedData['temp_min']),
            'temp_max' => round($weightedData['temp_max']),
            'rainfall_days' => round($weightedData['rainfall_days']),
            'rainfall_mm' => round($weightedData['rainfall_mm'], 1),
            'daylight_hours' => round($weightedData['daylight_hours'], 1),
            'humidity' => round($weightedData['humidity']),
            'advice' => $this->generateAdvice(
                round($weightedData['temp_min']),
                round($weightedData['temp_max']),
                round($weightedData['rainfall_days']),
                round($weightedData['rainfall_mm'], 1),
                round($weightedData['humidity']),
                '',
            ),
            'main_condition' => '',
            'is_forecast' => false,
        ];
    }

    /**
     * Analyse les données météo prévisionnelles
     */
    private function analyzeWeatherData(array $days): array
    {
        $weatherData = [
            'forecast' => true,
            'days' => [],
        ];

        foreach ($days as $day) {
            $weatherData['days'][$day['date']] = [
                'temperature' => [
                    'min' => $day['day']['mintemp_c'],
                    'max' => $day['day']['maxtemp_c'],
                ],
                'wind' => $day['day']['maxwind_kph'],
                'precipitation' => $day['day']['totalprecip_mm'],
                'snow' => $day['day']['totalsnow_cm'],
                'humidity' => $day['day']['avghumidity'],
                'uv' => round($day['day']['uv'], 0, PHP_ROUND_HALF_UP),
                'daylight' => round($this->calculateDaylight($day), 1),
                'condition' => [
                    'text' => $day['day']['condition']['text'],
                    'icon' => $day['day']['condition']['icon'],
                ],
                'advice' => $this->generateAdvice(
                    $day['day']['mintemp_c'],
                    $day['day']['maxtemp_c'],
                    $day['day']['totalprecip_mm'] > 0,
                    $day['day']['totalprecip_mm'],
                    $day['day']['avghumidity'],
                    $day['day']['condition']['text'],
                    true
                )
            ];
        }

        return $weatherData;
    }

    /**
     * Calcule les heures de jour (lever/coucher du soleil)
     */
    private function calculateDaylight(array $day): float
    {
        // Si l'API fournit les données astro (lever/coucher de soleil)
        if (isset($day['astro']['sunrise']) && isset($day['astro']['sunset'])) {
            $sunrise = $day['astro']['sunrise'];
            $sunset = $day['astro']['sunset'];

            $sunriseTime = \DateTime::createFromFormat('h:i A', $sunrise);
            $sunsetTime = \DateTime::createFromFormat('h:i A', $sunset);

            if ($sunriseTime && $sunsetTime) {
                $diff = $sunsetTime->diff($sunriseTime);
                return $diff->h + ($diff->i / 60);
            }
        }

        // Fallback : utiliser le mois pour estimer
        $month = (int)date('n');
        return $this->getFallbackDaylightHours($month);
    }

    /**
     * Fallback pour les heures de jour si pas de données astro
     */
    private function getFallbackDaylightHours(int $month): float
    {
        // Heures de jour approximatives pour latitude ~45°N (moyenne mondiale)
        $daylightByMonth = [
            1 => 9.0, 2 => 10.5, 3 => 12.0, 4 => 13.5, 5 => 15.0, 6 => 16.0,
            7 => 15.5, 8 => 14.5, 9 => 13.0, 10 => 11.5, 11 => 10.0, 12 => 8.5
        ];

        return $daylightByMonth[$month] ?? 12.0;
    }

    /**
     * Trouve l'élément le plus fréquent dans un tableau
     */
    private function getMostFrequent(array $items): string
    {
        if (empty($items)) return '';

        $counts = array_count_values($items);
        arsort($counts);
        return array_key_first($counts);
    }

    private function generateAdvice(
        int    $tempMin,
        int    $tempMax,
        int    $rainyDays,
        float  $totalPrecipMm,
        int    $avgHumidity,
        string $condition,
        bool   $isForecast = false
    ): string
    {
        $advices = [];

        if ($tempMax > 35) {
            $advices[] = "chaleur intense attendue : une hydratation régulière est indispensable";
            $advices[] = "prévoir des vêtements très légers et amples";
        } elseif ($tempMax > 30) {
            $advices[] = "temps très chaud : une protection solaire est nécessaire";
            $advices[] = "vêtements légers recommandés";
        } elseif ($tempMax > 25) {
            $advices[] = "temps chaud et agréable";
            if (($tempMax - $tempMin) > 10) {
                $advices[] = "prévoir une petite couche pour les soirées";
            }
        } elseif ($tempMax >= 15 && $tempMax <= 25 && $tempMin >= 10) {
            $advices[] = "températures douces et agréables";
            if (($tempMax - $tempMin) >= 8) {
                $advices[] = "prévoir des vêtements légers pour la journée et une petite couche pour les soirées plus fraîches";
            } elseif (($tempMax - $tempMin) >= 5) {
                $advices[] = "une veste légère peut être utile en soirée";
            }
        } elseif ($tempMin < 5 && $tempMin >= 0) {
            $advices[] = "températures fraîches nécessitant des vêtements chauds";
            $advices[] = "prévoir manteau, écharpe et gants";
        } elseif ($tempMin < 0) {
            $advices[] = "températures négatives : un équipement d'hiver est indispensable";
            $advices[] = "vêtements thermiques, manteau chaud, bonnet et gants recommandés";
        }

        if ($rainyDays > 15) {
            $advices[] = sprintf(
                "nombreux jours de pluie sont à prévoir (≈%s mm sur le mois) : un parapluie ou un manteau imperméable est indispensable",
                round($totalPrecipMm)
            );
        } elseif ($rainyDays >= 7) {
            $advices[] = sprintf(
                "plusieurs jours de pluie sont à prévoir (≈%s mm sur le mois) : un parapluie est recommandé",
                round($totalPrecipMm)
            );
        } elseif ($rainyDays > 2) {
            $advices[] = sprintf(
                "quelques averses sont possibles (≈%s mm sur le mois)",
                round($totalPrecipMm)
            );
        } elseif ($rainyDays <= 1 && $totalPrecipMm < 10 && $avgHumidity < 40) {
            $advices[] = "climat très sec : une hydratation régulière est recommandée";
        } elseif ($totalPrecipMm < 30 && $avgHumidity < 50) {
            $advices[] = "climat sec";
        } elseif ($rainyDays <= 2 && $totalPrecipMm < 20 && $avgHumidity > 70) {
            $advices[] = "peu de précipitations attendues";
        } elseif ($rainyDays === 0 && $totalPrecipMm < 5) {
            $advices[] = "temps sec";
        }

        if ($condition && stripos($condition, 'orage') !== false) {
            $advices[] = "risques d'orages";
        }

        if (empty($advices)) {
            return "Conditions météo généralement favorables pour la période.";
        }

        $sentences = array_map('ucfirst', $advices);
        $result = implode('. ', $sentences);
        if (!str_ends_with($result, '.')) {
            $result .= '.';
        }

        if (!$isForecast) {
            $result .= " (Moyennes sur les 10 dernières années)";
        }

        return $result;
    }
}