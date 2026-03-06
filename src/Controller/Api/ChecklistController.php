<?php

namespace App\Controller\Api;

use App\Entity\ChecklistItem;
use App\Entity\ChecklistTemplate;
use App\Entity\ChecklistTemplateItem;
use App\Entity\Trip;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/checklist', name: 'api_checklist_')]
class ChecklistController extends AbstractController
{
    public function __construct(readonly ManagerRegistry $managerRegistry)
    {
    }

    #[Route('/{trip}/items', name: 'items', requirements: ['trip' => '\d+'], methods: ['GET'])]
    #[IsGranted('view', subject: 'trip')]
    public function getItems(?Trip $trip = null): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $items = $this->managerRegistry->getRepository(ChecklistItem::class)->findForTrip($trip, $user);

        $shared = [];
        $private = [];

        foreach ($items as $item) {
            $category = $item->getCategory() ?: 'Divers';
            $data = [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'isChecked' => $item->isChecked(),
                'checkedBy' => $item->getCheckedBy()?->getCompleteName(),
                'position' => $item->getPosition(),
                'isOwner' => $item->getOwner()->getId() === $this->getUser()->getId(),
            ];

            if ($item->isShared()) {
                $shared[$category][] = $data;
            } else {
                $private[$category][] = $data;
            }
        }

        return $this->json(['shared' => $shared, 'private' => $private]);
    }

    #[Route('/{trip}/item/create', name: 'create', requirements: ['trip' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function createItem(Request $request, ?Trip $trip = null): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $name = trim($data['name'] ?? '');

        if (empty($name)) {
            return $this->json(['message' => 'Le nom de l\'item est requis.'], Response::HTTP_BAD_REQUEST);
        }

        $item = (new ChecklistItem())
            ->setTrip($trip)
            ->setOwner($this->getUser())
            ->setName($name)
            ->setCategory($data['category'] ?? null)
            ->setIsShared((bool)($data['isShared'] ?? true))
            ->setPosition((int)($data['position'] ?? 0));

        $this->managerRegistry->getManager()->persist($item);
        $this->managerRegistry->getManager()->flush();

        return $this->json([
            'message' => 'Item ajouté avec succès.',
            'item' => [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'isChecked' => $item->isChecked(),
                'checkedBy' => null,
                'position' => $item->getPosition(),
                'isOwner' => true,
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{trip}/item/{item}/toggle', name: 'toggle', requirements: ['trip' => '\d+', 'item' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function toggleItem(?Trip $trip = null, ?ChecklistItem $item = null): JsonResponse
    {
        $item->setIsChecked(!$item->isChecked());
        $item->setCheckedBy($item->isChecked() ? $this->getUser() : null);

        $this->managerRegistry->getManager()->flush();

        return $this->json([
            'isChecked' => $item->isChecked(),
            'checkedBy' => $item->getCheckedBy()?->getCompleteName(),
        ]);
    }

    #[Route('/{trip}/item/{item}/edit', name: 'edit', requirements: ['trip' => '\d+', 'item' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function editItem(Request $request, ?Trip $trip = null, ?ChecklistItem $item = null): JsonResponse
    {
        if (!$item) return $this->json(['message' => 'Item introuvable.'], Response::HTTP_NOT_FOUND);

        if ($item->getOwner()->getId() !== $this->getUser()->getId()) {
            return $this->json(['message' => 'Vous ne pouvez modifier que vos propres items.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $name = trim($data['name'] ?? '');

        if (empty($name)) {
            return $this->json(['message' => 'Le nom de l\'item est requis.'], Response::HTTP_BAD_REQUEST);
        }

        $item->setName($name);
        if (isset($data['category'])) {
            $item->setCategory($data['category']);
        }

        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => 'Item modifié avec succès.']);
    }

    #[Route('/{trip}/item/{item}/delete', name: 'delete', requirements: ['trip' => '\d+', 'item' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function deleteItem(?Trip $trip = null, ?ChecklistItem $item = null): JsonResponse
    {
        if (!$item) return $this->json(['message' => 'Item introuvable.'], Response::HTTP_NOT_FOUND);

        if ($item->getOwner()->getId() !== $this->getUser()->getId()) {
            return $this->json(['message' => 'Vous ne pouvez supprimer que vos propres items.'], Response::HTTP_FORBIDDEN);
        }

        $this->managerRegistry->getManager()->remove($item);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => 'Item supprimé avec succès.']);
    }

    #[Route('/templates', name: 'templates', methods: ['GET'])]
    public function getTemplates(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) return new JsonResponse([], Response::HTTP_UNAUTHORIZED);

        $templates = $this->managerRegistry->getRepository(ChecklistTemplate::class)->findByOwner($user);

        $result = array_map(fn(ChecklistTemplate $t) => [
            'id' => $t->getId(),
            'name' => $t->getName(),
            'itemCount' => $t->getItems()->count(),
            'createdAt' => $t->getCreatedAt()->format('d/m/Y'),
        ], $templates);

        return $this->json($result);
    }

    #[Route('/{trip}/save-template', name: 'save_template', requirements: ['trip' => '\d+'], methods: ['POST'])]
    #[IsGranted('view', subject: 'trip')]
    public function saveTemplate(Request $request, ?Trip $trip = null): JsonResponse
    {
        if (!$trip) return $this->json(['message' => 'Voyage introuvable.'], Response::HTTP_NOT_FOUND);

        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $name = trim($data['name'] ?? '');

        if (empty($name)) {
            return $this->json(['message' => 'Le nom du template est requis.'], Response::HTTP_BAD_REQUEST);
        }

        $items = $this->managerRegistry->getRepository(ChecklistItem::class)->findForTrip($trip, $user);

        $template = (new ChecklistTemplate())
            ->setOwner($user)
            ->setName($name);

        $this->managerRegistry->getManager()->persist($template);

        foreach ($items as $item) {
            $templateItem = (new ChecklistTemplateItem())
                ->setTemplate($template)
                ->setName($item->getName())
                ->setCategory($item->getCategory())
                ->setIsShared($item->isShared())
                ->setPosition($item->getPosition());

            $this->managerRegistry->getManager()->persist($templateItem);
        }

        $this->managerRegistry->getManager()->flush();

        return $this->json([
            'message' => 'Template sauvegardé avec succès.',
            'template' => [
                'id' => $template->getId(),
                'name' => $template->getName(),
                'itemCount' => count($items),
                'createdAt' => $template->getCreatedAt()->format('d/m/Y'),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{trip}/apply-template/{template}', name: 'apply_template', requirements: ['trip' => '\d+', 'template' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function applyTemplate(?Trip $trip = null, ?ChecklistTemplate $template = null): JsonResponse
    {
        if (!$template) return $this->json(['message' => 'Template ou voyage introuvable.'], Response::HTTP_NOT_FOUND);

        if ($template->getOwner()->getId() !== $this->getUser()->getId()) {
            return $this->json(['message' => 'Accès refusé.'], Response::HTTP_FORBIDDEN);
        }

        foreach ($template->getItems() as $templateItem) {
            $item = (new ChecklistItem())
                ->setTrip($trip)
                ->setOwner($this->getUser())
                ->setName($templateItem->getName())
                ->setCategory($templateItem->getCategory())
                ->setIsShared($templateItem->isShared())
                ->setPosition($templateItem->getPosition())
                ->setIsChecked(false);

            $this->managerRegistry->getManager()->persist($item);
        }

        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => 'Template appliqué avec succès.']);
    }

    #[Route('/template/{template}/delete', name: 'delete_template', requirements: ['template' => '\d+'], methods: ['DELETE'])]
    public function deleteTemplate(?ChecklistTemplate $template = null): JsonResponse
    {
        if (!$this->getUser()) return new JsonResponse([], Response::HTTP_UNAUTHORIZED);

        if (!$template) {
            return $this->json(['message' => 'Template introuvable.'], Response::HTTP_NOT_FOUND);
        }

        if ($template->getOwner()->getId() !== $this->getUser()->getId()) {
            return $this->json(['message' => 'Accès refusé.'], Response::HTTP_FORBIDDEN);
        }

        $this->managerRegistry->getManager()->remove($template);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => 'Template supprimé avec succès.']);
    }
}
