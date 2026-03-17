<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\TripRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SitemapController extends AbstractController
{
    public function __construct(
        private readonly TripRepository $tripRepository,
    )
    {
    }

    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function index(): Response
    {
        $frontendUrl = $this->getParameter('domain');

        $staticPages = [
            ['loc' => $frontendUrl . '/', 'lastmod' => '2026-03-02', 'changefreq' => 'monthly', 'priority' => '1.0'],
            ['loc' => $frontendUrl . '/register', 'lastmod' => '2026-03-02', 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => $frontendUrl . '/login', 'lastmod' => '2026-03-02', 'changefreq' => 'monthly', 'priority' => '0.4'],
            ['loc' => $frontendUrl . '/legal-notice', 'lastmod' => '2026-03-02', 'changefreq' => 'yearly', 'priority' => '0.2'],
            ['loc' => $frontendUrl . '/privacy-policy', 'lastmod' => '2026-03-02', 'changefreq' => 'yearly', 'priority' => '0.2'],
        ];

        $publicTrips = $this->tripRepository->findBy(['isPublic' => true]);

        $tripUrls = array_map(fn($trip) => [
            'loc' => $frontendUrl . '/share/' . $trip->getPublicSlug(),
            'lastmod' => $trip->getDepartureDate()?->format('Y-m-d') ?? date('Y-m-d'),
            'changefreq' => 'weekly',
            'priority' => '0.7',
        ], $publicTrips);

        $xml = $this->renderView('sitemap.xml.twig', [
            'urls' => array_merge($staticPages, $tripUrls),
        ]);

        return new Response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
