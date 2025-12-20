<?php

namespace App\Controller\Api;

use App\Entity\Accommodation;
use App\Entity\Transport;
use App\Entity\Trip;
use App\Entity\TripTraveler;
use App\Service\AccommodationService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/transports/{trip}', name: 'api_transports_', requirements: ['trip' => '\d+'])]
class TransportController extends AbstractController
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
            $this->managerRegistry->getRepository(Transport::class)->findAllByTrip($trip)
        );
    }

    #[Route('/get/{transport}/form-data', name: 'getFormData', requirements: ['transport' => '\d+'], methods: ['GET'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function get(?Trip $trip = null, ?Transport $transport = null): JsonResponse
    {
        if (!$transport) {
            return $this->json(['message' => 'Edition impossible : transport non trouvé.'], 404);
        }

        return $this->json([
//            'name' => $transport->getName(),
//            'arrivalDate' => $accommodation->getArrivalDate()?->format('Y-m-d'),
//            'departureDate' => $accommodation->getDepartureDate()?->format('Y-m-d'),
//            'description' => $accommodation->getDescription(),
//            'price' => $accommodation->getPrice(),
//            'deposit' => $accommodation->getDeposit(),
//            'additionalExpensive' => $accommodation->getAdditionalExpensive(),
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

    #[Route('/delete/{transport}', name: 'delete', requirements: ['transport' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(?Trip $trip = null, ?Transport $transport = null): JsonResponse
    {
        if (!$transport) {
            return $this->json(['message' => 'Suppression impossible : transport non trouvé.'], 404);
        }

        $this->managerRegistry->getManager()->remove($transport);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => 'Transport supprimé avec succès.']);
    }

    #[Route('/update-reserved/{transport}', name: 'update_reserved', requirements: ['transport' => '\d+'], methods: ['PUT'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function UpdateReserved(Request $request, ?Trip $trip = null, ?Transport $transport = null): JsonResponse
    {
        if (!$transport) {
            return $this->json(['message' => 'Modification impossible : transport non trouvé.'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['reservedBy'])) {
            $transport->setPayedBy($this->managerRegistry->getRepository(TripTraveler::class)->find($data['reservedBy']));
        } else {
            $transport->setPayedBy(null);
        }

        $transport->setPaid(!$transport->isPaid());

        $this->managerRegistry->getManager()->persist($transport);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => 'Moyen de transport modifié avec succès.']);
    }
}