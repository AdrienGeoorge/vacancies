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

#[Route('/api/trip-travelers/{trip}', name: 'api_trip_traveler_', requirements: ['trip' => '\d+'])]
class TripTravelerController extends AbstractController
{
    private ManagerRegistry $managerRegistry;
    private TripService $tripService;

    protected string $domain;

    public function __construct(ManagerRegistry $managerRegistry, TripService $tripService, string $domain)
    {
        $this->managerRegistry = $managerRegistry;
        $this->tripService = $tripService;
        $this->domain = $domain;
    }

    #[Route('/delete/{traveler}', name: 'delete', requirements: ['traveler' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(Trip $trip, TripTraveler $traveler): JsonResponse
    {
        if ($traveler->getInvited() && $traveler->getInvited()->getCompleteName() === $trip->getTraveler()->getCompleteName()) {
            return $this->json(['message' => 'Vous ne pouvez pas supprimer le créateur du voyage.'], 403);
        }

        if ($traveler->getInvited() && $trip->getTraveler() !== $this->getUser()) {
            return $this->json(['message' => 'Vous ne pouvez pas supprimer un utilisateur invité à rejoindre ce séjour.'], 403);
        }

        try {
            $this->managerRegistry->getManager()->remove($traveler);
            $this->managerRegistry->getManager()->flush();

            return $this->json([
                'message' => 'Ce voyageur a bien été retiré du voyage.',
                'tripTravelers' => $trip->getTripTravelers()->toArray()
            ]);
        } catch (\Exception) {
            return $this->json(
                ['message' => 'Ce voyageur est rattaché à des éléments du voyage (logement, transport, activité ou dépense diverse...).
                Veuillez les supprimer ou les mettre à jour afin de pouvoir supprimer ce voyageur.'], 500
            );
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/invite', name: 'invite', requirements: ['trip' => '\d+'], methods: ['POST'])]
    #[IsGranted('invite', subject: 'trip', message: 'Vous n\'êtes pas autorisé à inviter quelqu\'un pour ce voyage.', statusCode: 403)]
    public function invite(Request $request, Trip $trip): Response
    {
        $data = json_decode($request->getContent(), true);

        $userToShareWith = $this->managerRegistry->getRepository(User::class)
            ->findOneBy(['email' => $data['email'] ?? null]);

        if (!$userToShareWith) {
            return $this->json(['message' => 'Aucun utilisateur n\'a été trouvé avec cette adresse mail : veuillez recommencer.'], 404);
        }

        $alreadyInvited = $this->managerRegistry->getRepository(ShareInvitation::class)
            ->getInvitationByUser($userToShareWith, $trip);

        if ($alreadyInvited) {
            return $this->json(['message' => 'Vous avez déjà invité cet utilisateur à rejoindre ce voyage.']);
        }

        $alreadyInTrip = $this->managerRegistry->getRepository(TripTraveler::class)
            ->findOneBy(['invited' => $userToShareWith, 'trip' => $trip]);

        if ($alreadyInTrip || $userToShareWith === $trip->getTraveler()) {
            return $this->json(['message' => 'Cet utilisateur a déjà rejoint ce voyage.']);
        }

        $token = $this->tripService->sendSharingMail($trip, $userToShareWith, $this->getUser()->getCompleteName());

        if ($token === false) {
            return $this->json(['message' => 'L\'email n\'a pas pu être envoyé en raison d\'une anomalie.'], 500);
        }

        $this->managerRegistry->getRepository(UserNotifications::class)->sendNotification(
            $userToShareWith,
            sprintf('vous a invité à prendre part au voyage : %s', $trip->getName()),
            $this->getUser(),
            $this->domain . '/trip/' . $trip->getId() . '/accept-invitation/' . $token
        );

        return $this->json(['message' => 'L\'invitation à prendre part à ce voyage a bien été transmise.'], 201);
    }

    #[Route('/accept/{token}', name: 'accept', requirements: ['token' => '\w+'], methods: ['GET'])]
    public function acceptInvitation(string $token, Trip $trip): JsonResponse
    {
        $invitation = $this->managerRegistry->getRepository(ShareInvitation::class)
            ->findOneBy(['token' => $token]);

        if (!$invitation) {
            return $this->json(['message' => 'Cette invitation n\'existe pas.'], 404);
        }

        if ($invitation->getExpireAt() < new \DateTimeImmutable('now')) {
            return $this->json(['message' => 'Cette invitation a expiré. La personne à l\'origine de cette requête doit renouveler l\'invitation.'], 500);
        }

        if ($invitation->getUserToShareWith() !== $this->getUser()) {
            return $this->json(['message' => 'Vous ne pouvez pas accepter une invitation qui ne vous est pas destinée.'], 500);
        }

        $traveler = new TripTraveler();
        $traveler->setTrip($invitation->getTrip());
        $traveler->setName($invitation->getUserToShareWith()->getCompleteName());
        $traveler->setInvited($invitation->getUserToShareWith());

        $this->managerRegistry->getManager()->persist($traveler);
        $this->managerRegistry->getManager()->remove($invitation);
        $this->managerRegistry->getManager()->flush();

        foreach ($invitation->getTrip()->getTripTravelers() as $tripTraveler) {
            $this->managerRegistry->getRepository(UserNotifications::class)->sendNotification(
                $tripTraveler->getInvited(),
                sprintf('a rejoint le voyage : %s', $invitation->getTrip()->getName()),
                $this->getUser(),
                $this->domain . '/trip/show/' . $trip->getId(),
            );
        }

        return $this->json([
            'message' => sprintf('Vous avez rejoint le voyage : %s.', $invitation->getTrip()->getName()),
            'id' => $trip->getId()
        ]);
    }
}