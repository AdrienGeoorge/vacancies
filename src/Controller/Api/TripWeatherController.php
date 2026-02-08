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
        $country = $trip->getCountry();

        if (!$country->getCapital()) {
            return new JsonResponse(['error' => 'Capitale non définie pour ce pays'], Response::HTTP_BAD_REQUEST);
        }

        $weatherData = $this->weatherService->getWeatherForTrip(
            $country->getCapital(),
            $trip->getDepartureDate(),
            $trip->getReturnDate()
        );

        if (!$weatherData) {
            return new JsonResponse(['message' => 'Données météo non disponibles'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($weatherData);
    }
}