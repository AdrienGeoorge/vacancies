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
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

#[Route('/api/weather', name: 'api_weather_')]
class TripWeatherController extends AbstractController
{
    public function __construct(
        private readonly WeatherService      $weatherService,
        private readonly TranslatorInterface $translator
    )
    {
    }

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/get/{trip}', name: 'get', requirements: ['trip' => '\d+'], methods: ['GET'])]
    #[IsGranted('view', subject: 'trip', message: 'trip.access.view_denied', statusCode: 403)]
    public function getWeather(?Trip $trip = null): JsonResponse
    {
        $cities = $this->weatherService->getCities($trip);

        if (empty($cities)) {
            return new JsonResponse(['error' => $this->translator->trans('weather.no_destination')], Response::HTTP_BAD_REQUEST);
        }

        $weatherByDestination = $this->weatherService->getWeatherByDestinations($cities, $trip);

        if (empty($weatherByDestination)) {
            return new JsonResponse(['message' => $this->translator->trans('weather.data_unavailable')], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($weatherByDestination);
    }
}