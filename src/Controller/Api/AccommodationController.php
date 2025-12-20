<?php

namespace App\Controller\Api;

use App\Entity\Accommodation;
use App\Entity\Trip;
use App\Entity\TripTraveler;
use App\Service\AccommodationService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/accommodations/{trip}', name: 'api_accommodations_', requirements: ['trip' => '\d+'])]
class AccommodationController extends AbstractController
{
    public function __construct(
        readonly ManagerRegistry      $managerRegistry,
        readonly AccommodationService $accommodationService
    )
    {
    }

    #[Route('/get-all', name: 'get_all', methods: ['GET'])]
    #[IsGranted('view', subject: 'trip')]
    public function getAll(?Trip $trip = null): JsonResponse
    {
        return $this->json(
            $this->managerRegistry->getRepository(Accommodation::class)->findAllByTrip($trip)
        );
    }

    #[Route('/get/{accommodation}/form-data', name: 'getFormData', requirements: ['accommodation' => '\d+'], methods: ['GET'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function get(?Trip $trip = null, ?Accommodation $accommodation = null): JsonResponse
    {
        if (!$accommodation) {
            return $this->json(['message' => 'Edition impossible : hébergement non trouvé.'], 404);
        }

        return $this->json([
            'name' => $accommodation->getName(),
            'address' => $accommodation->getAddress(),
            'zipCode' => $accommodation->getZipCode(),
            'city' => $accommodation->getCity(),
            'country' => $accommodation->getCountry(),
            'arrivalDate' => $accommodation->getArrivalDate()?->format('Y-m-d'),
            'departureDate' => $accommodation->getDepartureDate()?->format('Y-m-d'),
            'description' => $accommodation->getDescription(),
            'price' => $accommodation->getPrice(),
            'deposit' => $accommodation->getDeposit(),
            'additionalExpensive' => $accommodation->getAdditionalExpensive(),
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/create', name: 'create', methods: ['POST'])]
    #[Route('/edit/{accommodation}', name: 'edit', requirements: ['accommodation' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function create(Request $request, ?Trip $trip = null, ?Accommodation $accommodation = new Accommodation()): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        [$dto, $errors, $sentIds] = $this->accommodationService->initDtoFromRequest($data);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                echo $error->getPropertyPath();
                return $this->json(['message' => $error->getMessage()], 400);
            }
        }

        try {
            $this->accommodationService->handleAccommodationForm($trip, $accommodation, $dto, $sentIds);

            if ($request->get('_route') === 'api_accommodations_edit') {
                return $this->json(['message' => 'Les informations de ton hébergement ont bien été modifiées.']);
            }

            return $this->json(['message' => 'Cet hébergement a bien été ajouté à votre voyage.']);
        } catch (\Exception) {
            return $this->json(['message' => 'Une erreur est survenue lors de la création de l\'hébergement.'], 400);
        }
    }

    #[Route('/delete/{accommodation}', name: 'delete', requirements: ['accommodation' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(?Trip $trip = null, ?Accommodation $accommodation = null): JsonResponse
    {
        if (!$accommodation) {
            return $this->json(['message' => 'Suppression impossible : hébergement non trouvé.'], 404);
        }

        $this->managerRegistry->getManager()->remove($accommodation);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => 'Hébergement supprimé avec succès.']);
    }

    #[Route('/update-reserved/{accommodation}', name: 'update_reserved', requirements: ['accommodation' => '\d+'], methods: ['PUT'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function UpdateReserved(Request $request, ?Trip $trip = null, ?Accommodation $accommodation = null): JsonResponse
    {
        if (!$accommodation) {
            return $this->json(['message' => 'Modification impossible : hébergement non trouvé.'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['reservedBy'])) {
            $accommodation->setPayedBy($this->managerRegistry->getRepository(TripTraveler::class)->find($data['reservedBy']));
        } else {
            $accommodation->setPayedBy(null);
        }

        $accommodation->setBooked(!$accommodation->isBooked());

        $this->managerRegistry->getManager()->persist($accommodation);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => 'Hébergement modifié avec succès.']);
    }
}