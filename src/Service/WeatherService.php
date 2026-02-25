<?php

namespace App\Service;

use App\Entity\Trip;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class WeatherService
{
    private const API_BASE_URL = 'https://api.openweathermap.org/data/2.5';

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

    public function getWeatherByDestinations(array $cities, Trip $trip, bool $forExport = false): array
    {
        $weatherByDestination = [];

        foreach ($cities as $city) {
            if (!$city['name']) continue;

            $weatherData = $this->getWeatherForTrip(
                $city['name'],
                $city['country'],
                $city['arrivalDate'] ?: $trip->getDepartureDate(),
                $city['departureDate'] ?: $trip->getReturnDate(),
                $forExport
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
        ?\DateTime $returnDate,
        bool       $forExport = false
    ): ?array
    {
        try {
            if (!$departureDate) return null;

            if (false === $forExport) {
                $today = (new \DateTime())->setTime(0, 0, 0);

                // Utiliser le forecast réel si le voyage chevauche la fenêtre des 5 prochains jours
                // (voyage en cours OU départ imminent)
                $tripIsActive = $returnDate >= $today;
                $departureSoonEnough = $departureDate <= (clone $today)->modify('+5 days');

                if ($tripIsActive && $departureSoonEnough) {
                    // Prévisions réelles, avec fallback sur les moyennes historiques
                    try {
                        $forecast = $this->getRealForecast($cityName, $country, $departureDate, $returnDate);
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
            }

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
     * Prévisions réelles J+5 via OpenWeatherMap (créneaux 3h)
     * @throws \Exception
     */
    private function getRealForecast(
        string    $cityName,
        string    $country,
        \DateTime $departureDate,
        \DateTime $returnDate
    ): array
    {
        $daysToFetch = min(5, (new \DateTime())->diff($returnDate)->days + 1);
        $cnt = $daysToFetch * 8; // 8 créneaux de 3h par jour

        $coords = $this->weatherDataService->getCoordsForCity($cityName, $country);

        if (!$coords) {
            $this->logger->warning("Géocodage échoué", ['city' => $cityName, 'country' => $country]);
            return ['error' => true, 'message' => 'Ville introuvable'];
        }

        $response = $this->httpClient->request('GET', self::API_BASE_URL . '/forecast', [
            'query' => [
                'appid' => $this->weatherApiKey,
                'lat' => $coords['lat'],
                'lon' => $coords['lon'],
                'cnt' => $cnt,
                'units' => 'metric',
                'lang' => 'fr',
            ],
        ]);

        $data = $response->toArray();

        $airPollution = $this->fetchAirPollutionForecast($coords['lat'], $coords['lon']);

        return $this->analyzeWeatherData($data['list'] ?? [], $departureDate, $returnDate, $data['city'] ?? [], $airPollution);
    }

    /**
     * Récupère les prévisions de qualité de l'air (AQI 1–5) par jour
     */
    private function fetchAirPollutionForecast(float $lat, float $lon): array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/air_pollution/forecast', [
                'query' => [
                    'appid' => $this->weatherApiKey,
                    'lat' => $lat,
                    'lon' => $lon,
                ],
            ]);

            $aqiByDay = [];
            foreach ($response->toArray()['list'] ?? [] as $entry) {
                $date = date('Y-m-d', $entry['dt']);
                $aqiByDay[$date] = max($aqiByDay[$date] ?? 1, $entry['main']['aqi']);
            }

            return $aqiByDay;
        } catch (\Exception $e) {
            $this->logger->warning('Air Pollution API indisponible', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * @throws \Exception
     */
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
     * Analyse les données météo prévisionnelles OpenWeatherMap (créneaux 3h)
     * @throws \Exception
     */
    private function analyzeWeatherData(array $intervals, \DateTime $departureDate, \DateTime $returnDate, array $city = [], array $airPollution = []): array
    {
        $weatherData = [
            'forecast' => true,
            'days' => [],
        ];

        $departureDateStr = $departureDate->format('Y-m-d');
        $returnDateStr = $returnDate->format('Y-m-d');

        // Grouper les créneaux par date
        $byDay = [];
        foreach ($intervals as $interval) {
            $date = substr($interval['dt_txt'], 0, 10);
            $byDay[$date][] = $interval;
        }

        foreach ($byDay as $date => $dayIntervals) {
            // Exclure les jours avant la date de départ et après la date de retour
            if ($date < $departureDateStr || $date > $returnDateStr) continue;

            $tempsMin = array_map(fn($i) => $i['main']['temp_min'], $dayIntervals);
            $tempsMax = array_map(fn($i) => $i['main']['temp_max'], $dayIntervals);
            $humidities = array_map(fn($i) => $i['main']['humidity'], $dayIntervals);
            $windSpeeds = array_map(fn($i) => $i['wind']['speed'] ?? 0, $dayIntervals);
            $precipMm = array_sum(array_map(fn($i) => $i['rain']['3h'] ?? 0, $dayIntervals));
            $snowCm = array_sum(array_map(fn($i) => ($i['snow']['3h'] ?? 0) / 10, $dayIntervals));

            $visibilityValues = array_filter(array_map(fn($i) => $i['visibility'] ?? null, $dayIntervals));
            $minVisibility = !empty($visibilityValues) ? (int)min($visibilityValues) : null;

            $noonInterval = $this->getNoonInterval($dayIntervals);
            $condition = $noonInterval['weather'][0] ?? [];

            $tempMin = (int)round(min($tempsMin));
            $tempMax = (int)round(max($tempsMax));
            $humidity = (int)round(array_sum($humidities) / count($humidities));
            $aqi = $airPollution[$date] ?? null;

            $weatherData['days'][$date] = [
                'temperature' => [
                    'min' => $tempMin,
                    'max' => $tempMax,
                ],
                'visibility' => $minVisibility,  // pire de la journée
                'wind' => round(max($windSpeeds) * 3.6, 1), // m/s → km/h
                'precipitation' => round($precipMm, 1),
                'snow' => round($snowCm, 1),
                'humidity' => $humidity,
                'aqi' => $aqi, // 1=Bon 2=Correct 3=Modéré 4=Mauvais 5=Très mauvais
                'daylight' => round($this->calculateDaylightFromCity($city, $date), 1),
                'condition' => [
                    'text' => $condition['description'] ?? '',
                    'icon' => isset($condition['icon'])
                        ? 'https://openweathermap.org/img/wn/' . $condition['icon'] . '@2x.png'
                        : '',
                ],
                'advice' => $this->generateAdvice(
                    $tempMin,
                    $tempMax,
                    0,
                    round($precipMm, 1),
                    $humidity,
                    $condition['description'] ?? '',
                    true,
                    round(max($windSpeeds) * 3.6, 1),
                    $snowCm,
                    $minVisibility,
                    $aqi
                ),
            ];
        }

        return $weatherData;
    }

    /**
     * Retourne le créneau le plus proche de midi pour représenter la journée
     */
    private function getNoonInterval(array $intervals): array
    {
        $best = null;
        $minDiff = PHP_INT_MAX;

        foreach ($intervals as $interval) {
            $hour = (int)substr($interval['dt_txt'], 11, 2);
            $diff = abs($hour - 12);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $best = $interval;
            }
        }

        return $best ?? $intervals[0];
    }

    /**
     * Calcule les heures de jour depuis les données city OWM (timestamps Unix)
     * @throws \Exception
     */
    private function calculateDaylightFromCity(array $city, string $date): float
    {
        if (isset($city['sunrise'], $city['sunset'])) {
            $sunrise = new \DateTime('@' . $city['sunrise']);
            $sunset = new \DateTime('@' . $city['sunset']);
            $diff = $sunset->diff($sunrise);

            return $diff->h + ($diff->i / 60);
        }

        return $this->getFallbackDaylightHours((int)date('n', strtotime($date)));
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

    private function generateAdvice(
        int    $tempMin,
        int    $tempMax,
        int    $rainyDays,
        float  $totalPrecipMm,
        int    $avgHumidity,
        string $condition,
        bool   $isForecast = false,
        ?float $windKph = null,
        ?float $snowCm = null,
        ?int   $visibilityM = null,
        ?int   $aqi = null
    ): string
    {
        $advices = [];
        $condLower = mb_strtolower($condition);

        if ($tempMax > 35) {
            $advices[] = "chaleur intense : hydratation régulière indispensable, protection solaire indispensable";
            $advices[] = "vêtements très légers et amples recommandés";
        } elseif ($tempMax > 30) {
            $advices[] = "temps très chaud : protection solaire nécessaire";
            $advices[] = "vêtements légers recommandés";
        } elseif ($tempMax > 25) {
            $advices[] = "temps chaud et agréable";
            if (($tempMax - $tempMin) > 10) {
                $advices[] = "prévoir une couche pour les soirées";
            }
        } elseif ($tempMax >= 15 && $tempMin >= 10) {
            $advices[] = "températures douces et agréables";
            if (($tempMax - $tempMin) >= 8) {
                $advices[] = "prévoir des vêtements légers pour la journée et une couche pour les soirées plus fraîches";
            } elseif (($tempMax - $tempMin) >= 5) {
                $advices[] = "une veste légère peut être utile en soirée";
            }
        } elseif ($tempMin >= 0) {
            $advices[] = "températures fraîches : vêtements chauds nécessaires";
            $advices[] = "prévoir manteau, écharpe et gants";
        } else {
            $advices[] = "températures négatives : équipement hivernal indispensable";
            $advices[] = "vêtements thermiques, manteau chaud, écharpe, bonnet et gants recommandés";
        }

        if ($isForecast) {
            // Neige (prioritaire sur la pluie si les deux sont présents)
            if ($snowCm !== null && $snowCm >= 0.5) {
                if ($snowCm >= 10) {
                    $advices[] = sprintf("chutes de neige importantes prévues (≈%.0f cm) : chaussures imperméables indispensables", $snowCm);
                } elseif ($snowCm >= 2) {
                    $advices[] = sprintf("neige attendue (≈%.0f cm) : chaussures adaptées recommandées", $snowCm);
                } else {
                    $advices[] = "quelques flocons possibles";
                }
            }

            // Pluie journalière
            if ($totalPrecipMm > 15) {
                $advices[] = sprintf("fortes pluies prévues (≈%.0f mm) : imperméable indispensable", $totalPrecipMm);
            } elseif ($totalPrecipMm > 5) {
                $advices[] = sprintf("pluie prévue (≈%.0f mm) : prévoir un parapluie", $totalPrecipMm);
            } elseif ($totalPrecipMm > 1) {
                $advices[] = "quelques averses possibles : un parapluie est conseillé";
            } elseif ($totalPrecipMm > 0) {
                $advices[] = "légères précipitations possibles";
            } elseif ($avgHumidity < 40) {
                $advices[] = "temps sec : hydratation recommandée";
            }
        } else {
            // Mode historique : logique en jours/mois
            if ($rainyDays > 15) {
                $advices[] = sprintf(
                    "nombreux jours de pluie à prévoir (≈%s mm/mois) : imperméable indispensable",
                    round($totalPrecipMm)
                );
            } elseif ($rainyDays >= 7) {
                $advices[] = sprintf(
                    "plusieurs jours de pluie (≈%s mm/mois) : prévoir un parapluie",
                    round($totalPrecipMm)
                );
            } elseif ($rainyDays > 2) {
                $advices[] = sprintf("quelques averses possibles (≈%s mm/mois)", round($totalPrecipMm));
            } elseif ($totalPrecipMm < 10 && $avgHumidity < 40) {
                $advices[] = "climat très sec : hydratation régulière recommandée";
            } elseif ($totalPrecipMm < 30 && $avgHumidity < 50) {
                $advices[] = "climat sec";
            } elseif ($totalPrecipMm < 20 && $avgHumidity > 70) {
                $advices[] = "peu de précipitations attendues";
            }
        }

        if ($isForecast && $windKph !== null) {
            if ($windKph >= 90) {
                $advices[] = sprintf("vents très forts (%.0f km/h) : prudence lors des déplacements", $windKph);
            } elseif ($windKph >= 60) {
                $advices[] = sprintf("vent fort (%.0f km/h) : prévoir une veste coupe-vent", $windKph);
            } elseif ($windKph >= 40) {
                $advices[] = sprintf("vent modéré (%.0f km/h) : une veste peut être appréciée", $windKph);
            }
        }

        if ($isForecast && $visibilityM !== null) {
            if ($visibilityM < 200) {
                $advices[] = sprintf("brouillard très dense (visibilité ≈%d m) : déplacements très difficiles, extrême prudence", $visibilityM);
            } elseif ($visibilityM < 1000) {
                $advices[] = sprintf("visibilité très réduite (≈%d m) : prudence lors des déplacements", $visibilityM);
            } elseif ($visibilityM < 3000) {
                $advices[] = sprintf("visibilité limitée possible (≈%.1f km)", $visibilityM / 1000);
            }
        } elseif (str_contains($condLower, 'brouillard') || str_contains($condLower, 'brume')) {
            // Fallback si pas de donnée de visibilité
            $advices[] = "brouillard prévu : vigilance lors des déplacements";
        }

        if (str_contains($condLower, 'orage')) {
            $advices[] = "risques d'orages : éviter les zones exposées";
        } elseif (str_contains($condLower, 'grêle')) {
            $advices[] = "risque de grêle";
        }

        if ($isForecast && $aqi !== null && $aqi >= 3) {
            if ($aqi === 5) {
                $advices[] = "qualité de l'air très mauvaise : limiter les sorties au strict minimum, masque indispensable en extérieur";
            } elseif ($aqi === 4) {
                $advices[] = "qualité de l'air mauvaise : éviter les activités physiques en extérieur, masque conseillé";
            } else {
                $advices[] = "qualité de l'air modérée : les personnes sensibles doivent limiter les activités prolongées en extérieur";
            }
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