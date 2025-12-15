<?php

namespace App\Controller;

use App\Entity\ShareInvitation;
use App\Entity\Trip;
use App\Entity\TripTraveler;
use App\Entity\User;
use App\Entity\UserNotifications;
use App\Form\TripType;
use App\Service\FileUploaderService;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trip', name: 'trip_')]
class TripController extends AbstractController
{
    private ManagerRegistry $managerRegistry;
    private FileUploaderService $uploaderService;
    private TripService $tripService;

    public function __construct(ManagerRegistry $managerRegistry, FileUploaderService $uploaderService,
                                TripService     $tripService)
    {
        $this->managerRegistry = $managerRegistry;
        $this->uploaderService = $uploaderService;
        $this->tripService = $tripService;
    }

    #[Route('/show/{trip}/balance', name: 'balance_details', requirements: ['trip' => '\d+'])]
    #[IsGranted('view', subject: 'trip')]
    public function balance(Trip $trip): Response
    {
        return $this->render('trip/balance.html.twig', [
            'trip' => $trip,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
            'data' => $this->tripService->getCreditorAndDebtorDetails($trip)
        ]);
    }

    #[Route('/delete/{trip}', name: 'delete', requirements: ['trip' => '\d+'])]
    #[IsGranted('delete_trip', subject: 'trip')]
    public function delete(Trip $trip): Response
    {
        $this->managerRegistry->getManager()->remove($trip);
        $this->managerRegistry->getManager()->flush();

        $this->addFlash('success', 'Votre voyage a bien été supprimé.');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/update-bloc-notes/{trip}', name: 'update_bloc_notes', requirements: ['trip' => '\d+'], options: ['expose' => true])]
    #[IsGranted('view', 'trip')]
    public function updateBlocNotes(Request $request, Trip $trip): Response
    {
        if (!$request->isXmlHttpRequest()) {
            $this->addFlash('error', 'Une erreur est survenue. Veuillez recommencer.');
            return new JsonResponse([], 500);
        }

        $trip->setBlocNotes($request->request->get('blocNotes'));
        $this->managerRegistry->getManager()->persist($trip);
        $this->managerRegistry->getManager()->flush();

        return new JsonResponse([], 200);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/share/{trip}', name: 'share', requirements: ['trip' => '\d+'], options: ['expose' => true])]
    #[IsGranted('invite', subject: 'trip')]
    public function share(Request $request, Trip $trip): Response
    {
        if (!$request->isXmlHttpRequest()) {
            $this->addFlash('error', 'Une erreur est survenue. Veuillez recommencer.');
            return new JsonResponse([], 500);
        }

        if (!$this->isGranted('invite', $trip)) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à inviter quelqu\'un pour ce voyage.');
            return new JsonResponse([], 403);
        }

        $userToShareWith = $this->managerRegistry->getRepository(User::class)
            ->findOneBy(['email' => $request->request->get('email')]);

        if (!$userToShareWith) {
            $this->addFlash('warning', 'Aucun utilisateur n\'a été trouvé avec cette adresse mail. Veuillez recommencer.');
            return new JsonResponse([], 404);
        }

        $alreadyInvited = $this->managerRegistry->getRepository(ShareInvitation::class)
            ->getInvitationByUser($userToShareWith, $trip);

        if ($alreadyInvited) {
            $this->addFlash('warning', 'Vous avez déjà invité cet utilisateur à rejoindre ce séjour.');
            return new JsonResponse([], 200);
        }

        $alreadyInTrip = $this->managerRegistry->getRepository(TripTraveler::class)
            ->findOneBy(['invited' => $userToShareWith, 'trip' => $trip]);

        if ($alreadyInTrip || $userToShareWith === $trip->getTraveler()) {
            $this->addFlash('warning', 'Cet utilisateur a déjà rejoint ce séjour.');
            return new JsonResponse([], 200);
        }

        $token = $this->tripService->sendSharingMail($trip, $userToShareWith, $this->getUser()->getFirstname() . ' ' . $this->getUser()->getLastname());

        if ($token === false) {
            $this->addFlash('error', 'L\'email n\'a pas pu être envoyé en raison d\'une anomalie.');
            return new JsonResponse([], 500);
        }

        $this->managerRegistry->getRepository(UserNotifications::class)->sendNotification(
            $userToShareWith,
            sprintf('vous a invité à prendre part au voyage : %s', $trip->getName()),
            $this->getUser(),
            $this->generateUrl('trip_accept', ['token' => $token])
        );

        $this->addFlash('success', 'L\'invitation à prendre part à ce voyage a bien été transmise.');
        return new JsonResponse([], 201);
    }

    #[Route('/accept/{token}', name: 'accept', requirements: ['token' => '\w+'])]
    public function acceptInvitation(string $token): Response
    {
        $invitation = $this->managerRegistry->getRepository(ShareInvitation::class)
            ->findOneBy(['token' => $token]);

        if (!$invitation) {
            $this->addFlash('error', 'Nous n\'avons trouvé aucune invitation. Veuillez réessayer.');
            return $this->redirectToRoute('app_home');
        }

        if ($invitation->getExpireAt() < new \DateTimeImmutable('now')) {
            $this->addFlash('error', 'Cette invitation a expiré. La personne à l\'origine de cette requête doit renouveler l\'invitation.');
            return $this->redirectToRoute('app_home');
        }

        if ($invitation->getUserToShareWith() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas accepter une invitation qui ne vous est pas destinée.');
            return $this->redirectToRoute('app_home');
        }

        if (!$invitation->getUserToShareWith()->getFirstname() || !$invitation->getUserToShareWith()->getLastname()) {
            $this->addFlash('error', 'Vous devez remplir votre nom et prénom pour rejoindre ce voyage.');
            return $this->redirectToRoute('user_about');
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
                $this->generateUrl('trip_show', ['trip' => $invitation->getTrip()->getId()])
            );
        }

        $this->addFlash('success', sprintf('Vous avez rejoint le voyage %s.', $invitation->getTrip()->getName()));
        return $this->redirectToRoute('trip_show', ['trip' => $invitation->getTrip()->getId()]);
    }

    #[Route('/leave/{trip}', name: 'leave', requirements: ['trip' => '\d+'])]
    #[IsGranted('view', subject: 'trip')]
    public function leave(Trip $trip): RedirectResponse
    {
        if ($trip->getTraveler() !== $this->getUser()) {
            $traveler = $this->managerRegistry->getRepository(TripTraveler::class)->findOneBy(['trip' => $trip, 'invited' => $this->getUser()]);
            $this->managerRegistry->getManager()->remove($traveler);
            $this->managerRegistry->getManager()->flush();

            $this->addFlash('success', sprintf('Vous avez quitté le voyage : %s', $trip->getName()));
        } else {
            $this->addFlash('error', 'Vous n\'avez pas l\'autorisation de réaliser cette action.');
        }

        return $this->redirectToRoute('app_home');
    }
}