<?php

namespace App\Controller\Api;

use App\Entity\Trip;
use App\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

#[Route('/api/weather', name: 'api_weather_')]
class TripWeatherController extends AbstractController
{
    public function __construct(
        private readonly WeatherService $weatherService
    )
    {
    }

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/get/{trip}', name: 'get', requirements: ['trip' => '\d+'], methods: ['GET'])]
    #[IsGranted('view', subject: 'trip', message: 'Vous ne pouvez pas consulter ce voyage.', statusCode: 403)]
    public function getWeather(?Trip $trip = null): JsonResponse
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

        if (empty($cities)) {
            return new JsonResponse(['error' => 'Aucune destination définie pour ce voyage'], Response::HTTP_BAD_REQUEST);
        }

        $weatherByDestination = [];

        foreach ($cities as $city) {
            if (!$city['name']) continue;

            $weatherData = $this->weatherService->getWeatherForTrip(
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

        if (empty($weatherByDestination)) {
            return new JsonResponse(['message' => 'Données météo non disponibles'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($weatherByDestination);
    }
}