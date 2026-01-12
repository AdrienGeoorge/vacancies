<?php

namespace App\Controller\Api;

use App\DTO\VariousExpensiveRequestDTO;
use App\Entity\Currency;
use App\Entity\Trip;
use App\Entity\TripTraveler;
use App\Entity\VariousExpensive;
use App\Service\CurrencyConverterService;
use App\Service\DTOService;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/various-expensive/{trip}', name: 'api_various_expensive_', requirements: ['trip' => '\d+'])]
class VariousExpensiveController extends AbstractController
{
    public function __construct(
        readonly ManagerRegistry          $managerRegistry,
        readonly TripService              $tripService,
        readonly DTOService               $dtoService,
        readonly CurrencyConverterService $converterService,
    )
    {
    }

    #[Route('/get-all', name: 'get_all', methods: ['GET'])]
    #[IsGranted('view', subject: 'trip')]
    public function getAll(?Trip $trip = null): JsonResponse
    {
        return $this->json(
            $this->managerRegistry->getRepository(VariousExpensive::class)->findAllByTrip($trip)
        );
    }

    #[Route('/get/{expensive}/form-data', name: 'getFormData', requirements: ['expensive' => '\d+'], methods: ['GET'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function get(?Trip $trip = null, ?VariousExpensive $expensive = null): JsonResponse
    {
        if (!$expensive) {
            return $this->json(['message' => 'Edition impossible : dépense non trouvée.'], 404);
        }

        return $this->json([
            'name' => $expensive->getName(),
            'description' => $expensive->getDescription(),
            'originalPrice' => $expensive->getOriginalPrice(),
            'originalCurrency' => $expensive->getOriginalCurrency(),
            'perPerson' => $expensive->isPerPerson()
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/create', name: 'create', methods: ['POST'])]
    #[Route('/edit/{expensive}', name: 'edit', requirements: ['expensive' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function create(
        Request           $request,
        ?Trip             $trip = null,
        ?VariousExpensive $expensive = new VariousExpensive()
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $selectedCurrency = $data['originalCurrency'] ? $this->managerRegistry->getRepository(Currency::class)
            ->findOneBy(['code' => $data['originalCurrency']]) : null;

        $dto = new VariousExpensiveRequestDTO($selectedCurrency);
        $dto = $this->dtoService->initDto($data, $dto);

        if (is_array($dto) && isset($dto['error'])) return $this->json(...$dto['error']);

        try {
            $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);

            $expensive->setTrip($trip);
            $expensive->setPrice($dto->originalPrice);
            $expensive = $this->dtoService->mapToEntity($dto, $expensive);

            if ($eurCurrency !== $dto->originalCurrency && $expensive->getOriginalPrice() !== $dto->originalPrice) {
                $convertedDeposit = $this->converterService->convert($dto->originalPrice, $dto->originalCurrency, $eurCurrency);
                $expensive->setConvertedPrice($convertedDeposit['amount']);
                $expensive->setExchangeRate($convertedDeposit['rate']);
                $expensive->setConvertedAt(new \DateTimeImmutable());
            }

            $this->managerRegistry->getManager()->persist($expensive);
            $this->managerRegistry->getManager()->flush();

            if ($request->get('_route') === 'api_various_expensive_edit') {
                return $this->json(['message' => 'Les informations de ta dépense ont bien été modifiées.']);
            }

            return $this->json(['message' => 'Cette dépense a bien été ajoutée à votre voyage.']);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Une erreur est survenue lors de la création de cette dépense.'], 400);
        }
    }

    #[Route('/delete/{expensive}', name: 'delete', requirements: ['expensive' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(?Trip $trip = null, ?VariousExpensive $expensive = null): JsonResponse
    {
        if (!$expensive) {
            return $this->json(['message' => 'Suppression impossible : dépense non trouvée.'], 404);
        }

        $this->managerRegistry->getManager()->remove($expensive);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => 'Votre dépense a bien été dissociée de ce voyage et supprimée.']);
    }

    #[Route('/update-reserved/{expensive}', name: 'update_reserved', requirements: ['expensive' => '\d+'], methods: ['PUT'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function updateReserved(Request $request, ?Trip $trip = null, ?VariousExpensive $expensive = null): JsonResponse
    {
        if (!$expensive) {
            return $this->json(['message' => 'Modification impossible : dépense non trouvée.'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['reservedBy'])) {
            $expensive->setPayedBy($this->managerRegistry->getRepository(TripTraveler::class)->find($data['reservedBy']));
        } else {
            $expensive->setPayedBy(null);
        }

        $expensive->setPaid(!$expensive->isPaid());

        $this->managerRegistry->getManager()->persist($expensive);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => 'Dépense modifiée avec succès.']);
    }
}