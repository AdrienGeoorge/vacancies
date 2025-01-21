<?php

namespace App\Controller;

use App\Entity\EventType;
use App\Entity\PlanningEvent;
use App\Entity\Transport;
use App\Entity\Trip;
use App\Form\TransportFormType;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trip/show/{trip}/transports', name: 'trip_transports_', requirements: ['trip' => '\d+'])]
class TransportController extends AbstractController
{
    private ManagerRegistry $managerRegistry;
    private TripService $tripService;

    public function __construct(ManagerRegistry $managerRegistry, TripService $tripService)
    {
        $this->managerRegistry = $managerRegistry;
        $this->tripService = $tripService;
    }

    #[Route('/', name: 'index')]
    #[IsGranted('view', subject: 'trip')]
    public function transports(Trip $trip): Response
    {
        return $this->render('transports/index.html.twig', [
            'trip' => $trip,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
        ]);
    }

    #[Route('/new', name: 'new')]
    #[Route('/edit/{transport}', name: 'edit', requirements: ['transport' => '\d+'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function form(Request $request, Trip $trip, ?Transport $transport): Response
    {
        if (!$transport) {
            $transport = new Transport();
            $transport->setTrip($trip);
        }

        if ($transport->getTrip() !== $trip) {
            $this->addFlash('error', 'Ce moyen de transport n\'est pas associé à ce voyage. Vous ne pouvez pas y accéder.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(TransportFormType::class, $transport, ['trip' => $trip]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $error = false;

                if ($transport->getType()->getName() === 'Voiture') {
                    if ($transport->getEstimatedToll() === null || $transport->getEstimatedGasoline() === null) {
                        $this->addFlash('warning', 'Vous avez sélectionné le mode de transport "voiture" : vous devez saisir une estimation du prix du péage et du carburant.');
                        $error = true;
                    }
                } elseif ($transport->getType()->getName() === 'Transports en commun') {
                    if ($transport->getSubscriptionDuration() === null) {
                        $this->addFlash('warning', 'Vous avez sélectionné le mode de transport "transports en commun" : vous devez saisir une durée pour l\'abonnement.');
                        $error = true;
                    }
                }

                if ($transport->getType()->getName() !== 'Voiture' && $transport->getPrice() === null) {
                    $this->addFlash('warning', 'Vous devez saisir le coût de ce transport.');
                    $error = true;
                }

                if (!$error) {
                    $errorOnCompare = $this->tripService->compareElementDateBetweenTripDates($trip, $transport->getDepartureDate(), $transport->getArrivalDate());

                    if ($errorOnCompare === null) {
                        if ($transport->isPaid() && $transport->getType()->getName() !== 'Transports en commun' && !$transport->getPayedBy()) {
                            $this->addFlash('warning', 'Vous avez indiqué que la réservation a été effectuée mais n\'avez pas renseigné qui a payé.');
                        } else {
                            $newEvent = false;
                            $this->managerRegistry->getManager()->persist($transport);

                            if ($transport->getDepartureDate()) {
                                $event = $this->managerRegistry->getRepository(PlanningEvent::class)->findOneBy(['transport' => $transport]);

                                if (!$event) {
                                    $eventType = $this->managerRegistry->getRepository(EventType::class)->findOneBy(['name' => 'Transport']);
                                    $event = (new PlanningEvent())
                                        ->setTrip($trip)
                                        ->setTransport($transport)
                                        ->setType($eventType);
                                    $newEvent = true;
                                }

                                $event->setTitle(sprintf('%s - Destination : %s', $transport->getType()->getName(), $transport->getDestination()));
                                $event->setStart($transport->getDepartureDate());
                                if ($transport->getArrivalDate()) $event->setEnd($transport->getArrivalDate());

                                $this->managerRegistry->getManager()->persist($event);
                            }

                            $this->managerRegistry->getManager()->flush();

                            if ($request->get('_route') === 'trip_transports_edit') {
                                $this->addFlash('success', 'Les détails de votre moyen de transport ont bien été modifiés.');
                            } else {
                                $this->addFlash('success', 'Ce moyen de transport a bien été rattaché à votre voyage.');
                                if ($newEvent) $this->addFlash('success', 'Un évènement a été ajouté à votre planning pour ce transport.');
                            }

                            return $this->redirectToRoute('trip_transports_index', ['trip' => $trip->getId()]);
                        }
                    } else {
                        $this->addFlash('warning', $errorOnCompare);
                    }
                }
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Une erreur est survenue lors du rattachement du moyen de transport à votre voyage.');
            }
        }

        return $this->render('transports/form.html.twig', [
            'trip' => $trip,
            'transport' => $transport,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
            'form' => $form->createView()
        ]);
    }

    #[Route('/delete/{transport}', name: 'delete', requirements: ['transport' => '\d+'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(Trip $trip, Transport $transport): Response
    {
        $this->managerRegistry->getManager()->remove($transport);
        $this->managerRegistry->getManager()->flush();

        $this->addFlash('success', 'Votre moyen de transport a bien été dissocié de ce voyage et supprimé.');

        return $this->redirectToRoute('trip_transports_index', ['trip' => $trip->getId()]);
    }
}