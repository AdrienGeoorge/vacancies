<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Country;
use App\Entity\Trip;
use App\Entity\WishlistItem;
use App\Repository\WishlistItemRepository;
use App\Service\FileUploaderService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/wishlist', name: 'api_wishlist_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class WishlistController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry     $managerRegistry,
        private readonly FileUploaderService $uploaderService,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    #[Route('/{item}', name: 'get', requirements: ['item' => '\d+'], methods: ['GET'])]
    public function get(WishlistItem $item): JsonResponse
    {
        if ($item->getUser() !== $this->getUser()) {
            return $this->json(['message' => $this->translator->trans('wishlist.forbidden')], Response::HTTP_FORBIDDEN);
        }

        return $this->json($this->serialize($item));
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = $this->managerRegistry->getRepository(WishlistItem::class)
            ->findByUser($this->getUser());

        return $this->json(array_map(fn(WishlistItem $item) => $this->serialize($item), $items));
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $name = $request->request->get('name');

        if (!$name || trim($name) === '') {
            return $this->json(['message' => $this->translator->trans('wishlist.name_required')], Response::HTTP_BAD_REQUEST);
        }

        try {
            $country = null;
            $countryId = $request->request->get('countryId');
            if ($countryId) {
                $country = $this->managerRegistry->getRepository(Country::class)->find((int) $countryId);
            }

            $item = (new WishlistItem())
                ->setName(trim($name))
                ->setCountry($country)
                ->setNotes($request->request->get('notes') ?: null)
                ->setUser($this->getUser());

            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $imageFileName = $this->uploaderService->upload(
                    file: $imageFile,
                    directory: $this->getParameter('upload_directory') . '/wishlist'
                );

                $item->setImage('/' . $this->getParameter('upload_directory') . '/wishlist/' . $imageFileName);
            }

            $this->managerRegistry->getManager()->persist($item);
            $this->managerRegistry->getManager()->flush();

            return $this->json($this->serialize($item), Response::HTTP_CREATED);
        } catch (\Exception) {
            return $this->json(['message' => $this->translator->trans('wishlist.error')], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/edit/{item}', name: 'edit', requirements: ['item' => '\d+'], methods: ['POST'])]
    public function edit(Request $request, WishlistItem $item): JsonResponse
    {
        if ($item->getUser() !== $this->getUser()) {
            return $this->json(['message' => $this->translator->trans('wishlist.forbidden_action')], Response::HTTP_FORBIDDEN);
        }

        $name = $request->request->get('name');

        if (!$name || trim($name) === '') {
            return $this->json(['message' => $this->translator->trans('wishlist.name_required')], Response::HTTP_BAD_REQUEST);
        }

        try {
            $country = null;
            $countryId = $request->request->get('countryId');
            if ($countryId) {
                $country = $this->managerRegistry->getRepository(Country::class)->find((int) $countryId);
            }

            $item->setName(trim($name))
                ->setCountry($country)
                ->setNotes($request->request->get('notes') ?: null);

            $imageFile = $request->files->get('image');
            if ($imageFile) {
                if ($item->getImage()) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $item->getImage();
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $imageFileName = $this->uploaderService->upload(
                    file: $imageFile,
                    directory: $this->getParameter('upload_directory') . '/wishlist'
                );

                $item->setImage('/' . $this->getParameter('upload_directory') . '/wishlist/' . $imageFileName);
            } elseif ($request->request->get('removeImage')) {
                if ($item->getImage()) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $item->getImage();
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $item->setImage(null);
            }

            $this->managerRegistry->getManager()->flush();

            return $this->json($this->serialize($item));
        } catch (\Exception) {
            return $this->json(['message' => $this->translator->trans('wishlist.error')], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{item}/convert-to-trip/{trip}', name: 'convert', requirements: ['item' => '\d+', 'trip' => '\d+'], methods: ['POST'])]
    public function convertToTrip(WishlistItem $item, Trip $trip): JsonResponse
    {
        if ($item->getUser() !== $this->getUser() || $trip->getTraveler() !== $this->getUser()) {
            return $this->json(['message' => $this->translator->trans('wishlist.forbidden_action')], Response::HTTP_FORBIDDEN);
        }

        try {
            $transferred = [];

            if ($item->getNotes()) {
                $trip->setBlocNotes($item->getNotes());
                $transferred[] = 'notes';
            }

            if ($item->getImage()) {
                $oldAbsPath = $this->getParameter('kernel.project_dir') . '/public' . $item->getImage();
                $fileName = basename($item->getImage());
                $newRelPath = '/' . $this->getParameter('upload_directory') . '/' . $fileName;
                $newAbsPath = $this->getParameter('kernel.project_dir') . '/public' . $newRelPath;

                if (file_exists($oldAbsPath)) {
                    rename($oldAbsPath, $newAbsPath);
                    $trip->setImage($newRelPath);
                } else {
                    $trip->setImage($item->getImage());
                }

                $item->setImage(null);
                $transferred[] = 'image';
            }

            $this->managerRegistry->getManager()->persist($trip);
            $this->managerRegistry->getManager()->remove($item);
            $this->managerRegistry->getManager()->flush();

            return $this->json(['transferred' => $transferred]);
        } catch (\Exception) {
            return $this->json(['message' => $this->translator->trans('wishlist.error')], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/delete/{item}', name: 'delete', requirements: ['item' => '\d+'], methods: ['DELETE'])]
    public function delete(WishlistItem $item): JsonResponse
    {
        if ($item->getUser() !== $this->getUser()) {
            return $this->json(['message' => $this->translator->trans('wishlist.forbidden_action')], Response::HTTP_FORBIDDEN);
        }

        try {
            if ($item->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public' . $item->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $this->managerRegistry->getManager()->remove($item);
            $this->managerRegistry->getManager()->flush();

            return $this->json(['message' => $this->translator->trans('wishlist.deleted')]);
        } catch (\Exception) {
            return $this->json(['message' => $this->translator->trans('wishlist.error')], Response::HTTP_BAD_REQUEST);
        }
    }

    private function serialize(WishlistItem $item): array
    {
        $country = $item->getCountry();

        return [
            'id' => $item->getId(),
            'name' => $item->getName(),
            'country' => $country ? [
                'id' => $country->getId(),
                'code' => $country->getCode(),
                'name' => $country->getName(),
            ] : null,
            'image' => $item->getImage(),
            'notes' => $item->getNotes(),
            'createdAt' => $item->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
