<?php

namespace App\Controller\Api;

use App\Entity\ShareInvitation;
use App\Entity\Trip;
use App\Entity\TripTraveler;
use App\Entity\User;
use App\Entity\UserNotifications;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/trip-travelers/{trip}', name: 'api_trip_traveler_', requirements: ['trip' => '\d+'])]
class TripTravelerController extends AbstractController
{
    private ManagerRegistry $managerRegistry;
    private TripService $tripService;
    private TranslatorInterface $translator;

    protected string $domain;

    public function __construct(ManagerRegistry $managerRegistry, TripService $tripService, string $domain, TranslatorInterface $translator)
    {
        $this->managerRegistry = $managerRegistry;
        $this->tripService = $tripService;
        $this->domain = $domain;
        $this->translator = $translator;
    }

    #[Route('/delete/{traveler}', name: 'delete', requirements: ['traveler' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(Trip $trip, TripTraveler $traveler): JsonResponse
    {
        if ($traveler->getInvited() && $traveler->getInvited()->getCompleteName() === $trip->getTraveler()->getCompleteName()) {
            return $this->json(['message' => $this->translator->trans('traveler.delete.cannot_delete_creator')], Response::HTTP_FORBIDDEN);
        }

        if ($traveler->getInvited() && $trip->getTraveler() !== $this->getUser()) {
            return $this->json(['message' => $this->translator->trans('traveler.delete.cannot_delete_invited')], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->managerRegistry->getManager()->remove($traveler);
            $this->managerRegistry->getManager()->flush();

            return $this->json([
                'message' => $this->translator->trans('traveler.deleted'),
                'tripTravelers' => $trip->getTripTravelers()->toArray()
            ]);
        } catch (\Exception) {
            return $this->json(
                ['message' => $this->translator->trans('traveler.delete.error')], Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/invite', name: 'invite', requirements: ['trip' => '\d+'], methods: ['POST'])]
    #[IsGranted('invite', subject: 'trip', message: 'trip.access.invite_denied', statusCode: 403)]
    public function invite(Request $request, Trip $trip): Response
    {
        $data = json_decode($request->getContent(), true);
        $mail = $data['email'] ?? null;

        $userToShareWith = $this->managerRegistry->getRepository(User::class)
            ->findOneBy(['email' => $mail]);

        $alreadyInvited = $this->managerRegistry->getRepository(ShareInvitation::class)
            ->getInvitationByUserOrMail($userToShareWith, $mail, $trip);

        if ($alreadyInvited) {
            return $this->json(['message' => $this->translator->trans('traveler.invite.already_invited')]);
        }

        if ($userToShareWith) {
            $alreadyInTrip = $this->managerRegistry->getRepository(TripTraveler::class)
                ->findOneBy(['invited' => $userToShareWith, 'trip' => $trip]);

            if ($alreadyInTrip || $userToShareWith === $trip->getTraveler()) {
                return $this->json(['message' => $this->translator->trans('traveler.invite.already_in_trip')]);
            }
        }

        $token = $this->tripService->sendSharingMail($trip, $userToShareWith, $mail, $this->getUser()->getCompleteName());

        if ($token === false) {
            return $this->json(['message' => $this->translator->trans('traveler.invite.email_error')], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($userToShareWith) {
            $this->managerRegistry->getRepository(UserNotifications::class)->sendNotification(
                $userToShareWith,
                sprintf('vous a invité à prendre part au voyage : %s', $trip->getName()),
                $this->getUser(),
                '/trip/' . $trip->getId() . '/accept-invitation/' . $token
            );
        }

        return $this->json(['message' => $this->translator->trans('traveler.invite.sent')], Response::HTTP_CREATED);
    }

    #[Route('/get-infos/{token}', name: 'get_infos', requirements: ['token' => '\w+'], methods: ['GET'])]
    public function getInfos(string $token, Trip $trip): JsonResponse
    {
        $invitation = $this->managerRegistry->getRepository(ShareInvitation::class)
            ->findOneBy(['token' => $token]);

        if (!$invitation) {
            return $this->json(['message' => $this->translator->trans('traveler.invitation.not_found')], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'trip' => $trip,
            'creator' => $trip->getTraveler(),
            'budget' => $this->tripService->getBudget($trip),
        ]);
    }

    #[Route('/accept/{token}', name: 'accept', requirements: ['token' => '\w+'], methods: ['GET'])]
    public function acceptInvitation(string $token, Trip $trip): JsonResponse
    {
        $invitation = $this->managerRegistry->getRepository(ShareInvitation::class)
            ->findOneBy(['token' => $token]);

        if (!$invitation) {
            return $this->json(['message' => $this->translator->trans('traveler.invitation.not_found')], Response::HTTP_NOT_FOUND);
        }

        if (!$this->getUser()) {
            if ($invitation->getUserToShareWith() !== null) {
                return $this->json(['message' => $this->translator->trans('traveler.invitation.login_required')], Response::HTTP_UNAUTHORIZED);
            } else {
                return $this->json(['message' => $this->translator->trans('traveler.invitation.register_required')], Response::HTTP_UNAUTHORIZED);
            }
        }

        if ($invitation->getExpireAt() < new \DateTimeImmutable('now')) {
            return $this->json(['message' => $this->translator->trans('traveler.invitation.expired')], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (($invitation->getUserToShareWith() && $invitation->getUserToShareWith() !== $this->getUser()) || $this->getUser()->getEmail() !== $invitation->getEmail()) {
            return $this->json(['message' => $this->translator->trans('traveler.invitation.wrong_user')], Response::HTTP_FORBIDDEN);
        }

        $traveler = new TripTraveler();
        $traveler->setTrip($invitation->getTrip());
        $traveler->setName($this->getUser()->getCompleteName());
        $traveler->setInvited($this->getUser());

        $this->managerRegistry->getManager()->persist($traveler);
        $this->managerRegistry->getManager()->remove($invitation);
        $this->managerRegistry->getManager()->flush();

        foreach ($invitation->getTrip()->getTripTravelers() as $tripTraveler) {
            if ($tripTraveler->getInvited() === $this->getUser()) continue;

            $this->managerRegistry->getRepository(UserNotifications::class)->sendNotification(
                $tripTraveler->getInvited(),
                sprintf('a rejoint le voyage : %s', $invitation->getTrip()->getName()),
                $this->getUser(),
                '/trip/show/' . $trip->getId(),
            );
        }

        return $this->json([
            'message' => $this->translator->trans('traveler.invitation.accepted', ['%name%' => $invitation->getTrip()->getName()]),
            'id' => $trip->getId()
        ]);
    }
}