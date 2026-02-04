<?php

namespace App\Service;

use App\Entity\Accommodation;
use App\Entity\Activity;
use App\Entity\OnSiteExpense;
use App\Entity\ShareInvitation;
use App\Entity\Transport;
use App\Entity\Trip;
use App\Entity\User;
use App\Entity\VariousExpensive;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\String\ByteString;

class TripService
{
    private MailerInterface $mailer;
    private ManagerRegistry $managerRegistry;
    protected string $domain;

    public function __construct(MailerInterface $mailer, ManagerRegistry $managerRegistry, string $domain)
    {
        $this->mailer = $mailer;
        $this->managerRegistry = $managerRegistry;
        $this->domain = $domain;
    }

    /**
     * Compte le nombre de jours à attendre ou passés pour le voyage
     * @param Trip $trip
     * @return bool|array|string
     */
    public function countDaysBeforeOrAfter(Trip $trip): bool|array|string
    {
        $departureDate = $trip->getDepartureDate();

        if (!$departureDate) return false;

        if ($departureDate >= new DateTime('midnight')) {
            $diff = (new \DateTime('midnight'))->diff($departureDate);
            return [
                'before' => false,
                'days' => $diff->days
            ];
        }

        $returnDate = $trip->getReturnDate();
        if ($returnDate && $returnDate < new DateTime('midnight')) {
            $diff = (new \DateTime('midnight'))->diff($returnDate);
            return [
                'before' => true,
                'days' => $diff->days
            ];
        }

        return 'ongoing';
    }

    /**
     * Retourne le montant total des logement réservés
     * @param Trip $trip
     * @return float
     */
    public function getReservedAccommodationsPrice(Trip $trip): float
    {
        $price = 0;
        foreach ($trip->getAccommodations() as $accommodation) {
            if ($accommodation->isBooked()) {
                $price += $accommodation->getTotalPrice();
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des logement non réservés
     * @param Trip $trip
     * @return float
     */
    public function getNonReservedAccommodationsPrice(Trip $trip): float
    {
        $price = 0;
        foreach ($trip->getAccommodations() as $accommodation) {
            if (!$accommodation->isBooked()) {
                $price += $accommodation->getTotalPrice();
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des transports réservés
     * @param Trip $trip
     * @return float
     */
    public function getReservedTransportsPrice(Trip $trip): float
    {
        $price = 0;
        foreach ($trip->getTransports() as $transport) {
            if (!$transport->isPaid()) continue;

            if ($transport->isPerPerson() && $transport->getType()->getName() !== 'Voiture') {
                $price += ($transport->getOriginalCurrency()?->getCode() !== 'EUR'
                        ? $transport->getConvertedPrice()
                        : $transport->getOriginalPrice()) * $trip->getTripTravelers()->count();
            } else if (!$transport->isPerPerson() && $transport->getType()->getName() === 'Voiture') {
                $price += ($transport->getEstimatedToll() + $transport->getEstimatedGasoline());
            } else {
                $price += $transport->getOriginalCurrency()?->getCode() !== 'EUR'
                    ? $transport->getConvertedPrice()
                    : $transport->getOriginalPrice();
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des transports non réservés
     * @param Trip $trip
     * @return float
     */
    public function getNonReservedTransportsPrice(Trip $trip): float
    {
        $price = 0;
        foreach ($trip->getTransports() as $transport) {
            if ($transport->isPaid()) continue;

            if ($transport->isPerPerson() && $transport->getType()->getName() !== 'Voiture') {
                $price += ($transport->getOriginalCurrency()?->getCode() !== 'EUR'
                        ? $transport->getConvertedPrice()
                        : $transport->getOriginalPrice()) * $trip->getTripTravelers()->count();
            } else if (!$transport->isPerPerson() && $transport->getType()->getName() === 'Voiture') {
                $price += ($transport->getEstimatedToll() + $transport->getEstimatedGasoline());
            } else {
                $price += $transport->getOriginalCurrency()?->getCode() !== 'EUR'
                    ? $transport->getConvertedPrice()
                    : $transport->getOriginalPrice();
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des activités réservées
     * @param Trip $trip
     * @return float
     */
    public function getReservedActivitiesPrice(Trip $trip): float
    {
        $price = 0;
        foreach ($trip->getActivities() as $activity) {
            if ($activity->isBooked()) {
                if ($activity->isPerPerson()) {
                    $price += ($activity->getOriginalCurrency()?->getCode() !== 'EUR'
                            ? $activity->getConvertedPrice()
                            : $activity->getOriginalPrice()) * $trip->getTripTravelers()->count();
                } else {
                    $price += $activity->getOriginalCurrency()?->getCode() !== 'EUR'
                        ? $activity->getConvertedPrice()
                        : $activity->getOriginalPrice();
                }
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des activités non réservées
     * @param Trip $trip
     * @return float
     */
    public function getNonReservedActivitiesPrice(Trip $trip): float
    {
        $price = 0;
        foreach ($trip->getActivities() as $activity) {
            if (!$activity->isBooked()) {
                if ($activity->isPerPerson()) {
                    $price += ($activity->getOriginalCurrency()?->getCode() !== 'EUR'
                            ? $activity->getConvertedPrice()
                            : $activity->getOriginalPrice()) * $trip->getTripTravelers()->count();
                } else {
                    $price += $activity->getOriginalCurrency()?->getCode() !== 'EUR'
                        ? $activity->getConvertedPrice()
                        : $activity->getOriginalPrice();
                }
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des dépenses supplémentaires achetées
     * @param Trip $trip
     * @return float
     */
    public function getReservedVariousExpensivePrice(Trip $trip): float
    {
        $price = 0;
        foreach ($trip->getVariousExpensives() as $expensive) {
            if ($expensive->isPaid()) {
                if ($expensive->isPerPerson()) {
                    $price += ($expensive->getOriginalCurrency()?->getCode() !== 'EUR'
                            ? $expensive->getConvertedPrice()
                            : $expensive->getOriginalPrice()) * $trip->getTripTravelers()->count();
                } else {
                    $price += $expensive->getOriginalCurrency()?->getCode() !== 'EUR'
                        ? $expensive->getConvertedPrice()
                        : $expensive->getOriginalPrice();
                }
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des dépenses supplémentaires non achetées
     * @param Trip $trip
     * @return float
     */
    public function getNonReservedVariousExpensivePrice(Trip $trip): float
    {
        $price = 0;
        foreach ($trip->getVariousExpensives() as $expensive) {
            if (!$expensive->isPaid()) {
                if ($expensive->isPerPerson()) {
                    $price += ($expensive->getOriginalCurrency()?->getCode() !== 'EUR'
                            ? $expensive->getConvertedPrice()
                            : $expensive->getOriginalPrice()) * $trip->getTripTravelers()->count();
                } else {
                    $price += $expensive->getOriginalCurrency()?->getCode() !== 'EUR'
                        ? $expensive->getConvertedPrice()
                        : $expensive->getOriginalPrice();
                }
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des dépenses effectuées sur place
     * @param Trip $trip
     * @return float
     */
    public function getOnSiteExpensePrice(Trip $trip): float
    {
        $price = 0;

        foreach ($trip->getOnSiteExpenses() as $expense) {
            $price += $expense->getOriginalCurrency()?->getCode() !== 'EUR'
                ? $expense->getConvertedPrice()
                : $expense->getOriginalPrice();
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant des dépenses déjà payées et celles restantes à payer
     * @param Trip $trip
     * @return array
     */
    public function getBudget(Trip $trip): array
    {
        $onSite = $this->getOnSiteExpensePrice($trip);

        $reservedPrices = [
            'accommodations' => [
                'title' => 'Hébergements',
                'description' => 'Hôtel, auberge, location Airbnb, etc.',
                'amount' => $this->getReservedAccommodationsPrice($trip)
            ],
            'transports' => [
                'title' => 'Transports',
                'description' => 'Avion, train, taxi, bus, etc.',
                'amount' => $this->getReservedTransportsPrice($trip)
            ],
            'activities' => [
                'title' => 'Activités',
                'description' => "Musée, zoo, parc d'attractions, etc.",
                'amount' => $this->getReservedActivitiesPrice($trip)
            ],
            'various-expensive' => [
                'title' => 'Dépenses diverses',
                'description' => 'Assurance, VISA, forfait mobile, etc.',
                'amount' => $this->getReservedVariousExpensivePrice($trip)
            ],
            'on-site' => [
                'title' => 'Dépenses sur place',
                'description' => 'Courses, restaurant, etc.',
                'amount' => $onSite
            ]
        ];

        $nonReservedPrices = [
            'accommodations' => $this->getNonReservedAccommodationsPrice($trip),
            'transports' => $this->getNonReservedTransportsPrice($trip),
            'activities' => $this->getNonReservedActivitiesPrice($trip),
            'various-expensive' => $this->getNonReservedVariousExpensivePrice($trip),
        ];

        $totalReserved = round(array_sum(array_column($reservedPrices, 'amount')), 2);
        $totalNonReserved = round(array_sum($nonReservedPrices), 2);

        return [
            'toPay' => $totalNonReserved,
            'paid' => $totalReserved,
            'total' => round($totalNonReserved + $totalReserved, 2),
            'details' => [
                'reserved' => $reservedPrices,
                'nonReserved' => $nonReservedPrices,
                'on-site' => $onSite
            ],
        ];
    }

    /**
     * Retourne le montant des dépenses effectuées par voyageur
     * @param Trip $trip
     * @return array
     */
    public function getExpensesByTraveler(Trip $trip): array
    {
        $balances = [];
        $totalTravelers = $trip->getTripTravelers()->count();

        if ($totalTravelers > 0) {
            $totalPaid = round($this->getBudget($trip)['paid'], 2);
            $amountByPerson = round($totalPaid / $totalTravelers, 2);

            $allTransports = $this->managerRegistry->getRepository(Transport::class)
                ->findBy(['trip' => $trip]);

            foreach ($trip->getTripTravelers() as $traveler) {
                $accommodations = $this->managerRegistry->getRepository(Accommodation::class)
                    ->findByTraveler($trip, $traveler);
                $activities = $this->managerRegistry->getRepository(Activity::class)
                    ->findByTraveler($trip, $traveler);
                $variousExpenses = $this->managerRegistry->getRepository(VariousExpensive::class)
                    ->findByTraveler($trip, $traveler);
                $onSite = $this->managerRegistry->getRepository(OnSiteExpense::class)
                    ->findByTraveler($trip, $traveler);

                $transportPaid = 0;
                foreach ($allTransports as $transport) {
                    if ($transport->isPaid()) {
                        if ($transport->getType()->getName() === 'Voiture') {
                            $transportPaid += round(($transport->getEstimatedToll() + $transport->getEstimatedGasoline()) / $totalTravelers, 2);
                        } elseif ($transport->isPerPerson() && $transport->getPayedBy() === $traveler) {
                            if ($transport->getOriginalCurrency()->getCode() !== 'EUR') {
                                $transportPaid += round($transport->getConvertedPrice() * $totalTravelers, 2);
                            } else {
                                $transportPaid += round($transport->getOriginalPrice() * $totalTravelers, 2);
                            }
                        } elseif (!$transport->isPerPerson() && $transport->getPayedBy() === $traveler) {
                            if ($transport->getOriginalCurrency()->getCode() !== 'EUR') {
                                $transportPaid += $transport->getConvertedPrice();
                            } else {
                                $transportPaid += $transport->getOriginalPrice();
                            }
                        } elseif (!$transport->getPayedBy() && $transport->getType()->getName() === 'Transports en commun') {
                            if ($transport->isPerPerson()) {
                                if ($transport->getOriginalCurrency()->getCode() !== 'EUR') {
                                    $transportPaid += $transport->getConvertedPrice();
                                } else {
                                    $transportPaid += $transport->getOriginalPrice();
                                }
                            } else {
                                if ($transport->getOriginalCurrency()->getCode() !== 'EUR') {
                                    $transportPaid += round($transport->getConvertedPrice() / $totalTravelers, 2);
                                } else {
                                    $transportPaid += round($transport->getOriginalPrice() / $totalTravelers, 2);
                                }
                            }
                        }
                    }
                }

                $paid = round($accommodations + $transportPaid + $activities + $variousExpenses + $onSite, 2);

                $balances[$traveler->getName()] = [
                    'paid' => $paid,
                    'amountDue' => round($amountByPerson - $paid, 2),
                    'Hébergements' => round($accommodations, 2),
                    'Transports' => round($transportPaid, 2),
                    'Activités' => round($activities, 2),
                    'Dépenses diverses' => round($variousExpenses, 2),
                    'Dépenses sur place' => round($onSite, 2)
                ];
            }
        }

        return $balances;
    }

    /**
     * Retourne le montant des dépenses effectuées par voyageur
     * @param Trip $trip
     * @return array
     */
    public function getCreditorAndDebtorDetails(Trip $trip): array
    {
        $balances = $this->getExpensesByTraveler($trip);

        $creditors = []; // Ceux qui ont payé plus que leur part équitable
        $debtors = []; // Ceux qui ont payé moins que leur part équitable

        // Séparer les créditeurs et débiteurs
        foreach ($balances as $traveler => $balance) {
            if ($balance['amountDue'] < 0) { // Montant négatif : doit être remboursé
                $creditors[$traveler] = abs($balance['amountDue']);
            } elseif ($balance['amountDue'] > 0) { // Montant positif : doit payer
                $debtors[$traveler] = $balance['amountDue'];
            }
        }

        $data['balances'] = $balances;

        // Répartition des remboursements
        while (!empty($creditors) && !empty($debtors)) {
            // Obtenir le premier créditeur et débiteur
            $creditor = array_key_first($creditors);
            $debtor = array_key_first($debtors);

            $creditAmount = $creditors[$creditor];
            $debtAmount = $debtors[$debtor];

            // Montant à transférer
            $transferAmount = min($creditAmount, $debtAmount);

            // Ajouter la transaction
            $data['refund'][] = [
                'from' => $debtor,
                'to' => $creditor,
                'amount' => round($transferAmount, 2, PHP_ROUND_HALF_UP)
            ];

            // Mise à jour des soldes
            $creditors[$creditor] -= $transferAmount;
            $debtors[$debtor] -= $transferAmount;

            // Supprimer les clés si les soldes sont équilibrés
            if ($creditors[$creditor] <= 0) {
                unset($creditors[$creditor]);
            }
            if ($debtors[$debtor] <= 0) {
                unset($debtors[$debtor]);
            }
        }

        return $data;
    }

    /**
     * Retourne le planning des évènements
     * @param Trip $trip
     * @return array
     */
    public function getPlanning(Trip $trip): array
    {
        return [
            'start' => $trip->getDepartureDate()?->format('Y-m-d'),
            'end' => $trip->getReturnDate()?->add(new \DateInterval('P1D'))->format('Y-m-d'),
            'events' => $this->eventsToArray($trip)
        ];
    }

    /**
     * @throws Exception
     */
    public function getEventsByDay(Trip $trip): array
    {
        $events = $this->eventsToArray($trip);
        $eventsByDay = [];
        foreach ($events as $event) {
            $eventsByDay[(new \DateTime($event['start']))->format('Y-m-d')][] = $event;
        }

        return $eventsByDay;
    }

    /**
     * Retourne les évènements prévus dans le planning
     * @param Trip $trip
     * @return array
     */
    public function eventsToArray(Trip $trip): array
    {
        $events = [];
        foreach ($trip->getPlanningEvents()->toArray() as $event) {
            $events[] = [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'start' => $event->getStart()->format('Y-m-d H:i'),
                'end' => $event->getEnd()?->format('Y-m-d H:i'),
                'timeToGo' => $event->getTimeToGo(),
                'type' => $event->getType()->getName(),
                'color' => $event->getType()->getColor()
            ];
        }

        return $events;
    }

    /**
     * @param Trip $trip
     * @param DateTime|null $start
     * @param DateTime|null $end
     * @return string|null
     * @throws Exception
     */
    public function compareElementDateBetweenTripDates(Trip $trip, ?DateTime $start, ?DateTime $end = null): ?string
    {
        if ($start !== null || $end !== null) {
            $today = new DateTime('today');
            $departureDate = $trip->getDepartureDate()?->setTime(0, 0, 0);
            $returnDate = $trip->getReturnDate()?->setTime(23, 59, 59);

            if ($departureDate && $start && $start < $departureDate) {
                return 'La date de début ne peut pas être inférieure à la date de commencement du séjour.';
            }

            if ($departureDate && $end && $end < $departureDate) {
                return 'La date de fin ne peut pas être inférieure à la date de commencement du séjour.';
            }

            if (!$departureDate) {
                if ($start && $start < $today) {
                    return 'Comme vous n\'avez pas renseigné vos dates de séjour, votre événement ne peut pas commencer avant la date du jour.';
                }
                if ($end && $end < $today) {
                    return 'Comme vous n\'avez pas renseigné vos dates de séjour, votre événement ne peut pas se terminer avant la date du jour.';
                }
            }

            if ($returnDate && $start && $start > $returnDate) {
                return 'La date de début ne peut pas être supérieure à la date de fin du séjour.';
            }

            if ($returnDate && $end && $end > $returnDate) {
                return 'La date de fin ne peut pas être supérieure à la date de fin du séjour.';
            }

            if ($start && $end && $end < $start) {
                return 'L\'événement ne peut pas se terminer avant d\'avoir commencé.';
            }
        }

        return null;
    }

    /**
     * Envoi du mail de partage de voyage
     *
     * @param Trip $trip
     * @param User|null $userToShareWith
     * @param string|null $mail
     * @param string $invitedBy
     * @return false|ByteString
     * @throws TransportExceptionInterface
     */
    public function sendSharingMail(Trip $trip, ?User $userToShareWith, ?string $mail, string $invitedBy): bool|ByteString
    {
        try {
            $token = ByteString::fromRandom(50);
            $url = $this->domain . '/trip/' . $trip->getId() . '/accept-invitation/' . $token;

            $email = (new TemplatedEmail())
                ->from('no-reply@adriengeorge.fr')
                ->to($userToShareWith ? $userToShareWith->getEmail() : $mail)
                ->subject('Vacancies : invitation à rejoindre un voyage')
                ->htmlTemplate('trip/share-mail.html.twig')
                ->context(['url' => $url, 'invitedBy' => $invitedBy, 'trip' => $trip]);

            $this->mailer->send($email);
        } catch (Exception) {
            return false;
        }

        $invitation = new ShareInvitation();
        if ($userToShareWith) $invitation->setUserToShareWith($userToShareWith);
        $invitation->setEmail($mail);
        $invitation->setTrip($trip);
        $invitation->setToken($token);
        $invitation->setExpireAt(new \DateTimeImmutable('+120 minutes'));

        $this->managerRegistry->getManager()->persist($invitation);
        $this->managerRegistry->getManager()->flush();

        return $token;
    }
}