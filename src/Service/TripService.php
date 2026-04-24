<?php

namespace App\Service;

use App\Entity\Accommodation;
use App\Entity\Activity;
use App\Entity\Currency;
use App\Entity\OnSiteExpense;
use App\Entity\ShareInvitation;
use App\Entity\Transport;
use App\Entity\Trip;
use App\Entity\User;
use App\Entity\VariousExpensive;
use App\Service\CurrencyConverterService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\String\ByteString;
use Symfony\Contracts\Translation\TranslatorInterface;

class TripService
{
    private MailerInterface $mailer;
    private ManagerRegistry $managerRegistry;
    protected string $domain;
    protected string $fromMail;
    protected string $appName;
    private WeatherService $weatherService;
    private TranslatorInterface $translator;
    private CurrencyConverterService $currencyConverterService;

    public function __construct(
        MailerInterface     $mailer,
        ManagerRegistry     $managerRegistry,
        WeatherService      $weatherService,
        TranslatorInterface $translator,
        string              $domain,
        string              $fromMail,
        string              $appName,
        CurrencyConverterService $currencyConverterService
    )
    {
        $this->mailer = $mailer;
        $this->managerRegistry = $managerRegistry;
        $this->weatherService = $weatherService;
        $this->translator = $translator;
        $this->domain = $domain;
        $this->fromMail = $fromMail;
        $this->appName = $appName;
        $this->currencyConverterService = $currencyConverterService;
    }

    /**
     * Compte le nombre de jours à attendre ou passés pour le voyage
     * @param Trip $trip
     * @return bool|array|string
     */
    public function countDaysBeforeOrAfter(Trip $trip): bool|array|string
    {
        $departureDate = $trip->getDepartureDate();
        $departureDate?->setTime(0, 0, 0);

        if (!$departureDate) return false;

        if ($departureDate > new DateTime('midnight')) {
            $diff = (new \DateTime('midnight'))->diff($departureDate);
            return [
                'before' => false,
                'days' => $diff->days
            ];
        }

        $returnDate = $trip->getReturnDate();
        $returnDate?->setTime(0, 0, 0);

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
     * @throws Exception
     */
    public function getReservedAccommodationsPrice(Trip $trip): float
    {
        $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);
        $price = 0;

        foreach ($trip->getAccommodations() as $accommodation) {
            if ($accommodation->isBooked()) {
                [$location, $deposit, $totalPrice] = $accommodation->getTotalPrices();

                if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                    $totalPrice = $this->currencyConverterService->convert(
                        $totalPrice,
                        $eurCurrency,
                        $trip->getCurrency(),
                        $accommodation->getConvertedAt() ?? $accommodation->getPurchaseDate()
                    )['amount'];
                }

                $price += $totalPrice;
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des logement non réservés
     * @param Trip $trip
     * @return float
     * @throws Exception
     */
    public function getNonReservedAccommodationsPrice(Trip $trip): float
    {
        $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);
        $price = 0;

        foreach ($trip->getAccommodations() as $accommodation) {
            if (!$accommodation->isBooked()) {
                [$location, $deposit, $totalPrice] = $accommodation->getTotalPrices();

                if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                    $totalPrice = $this->currencyConverterService->convert(
                        $totalPrice,
                        $eurCurrency,
                        $trip->getCurrency(),
                        $accommodation->getConvertedAt() ?? $accommodation->getPurchaseDate()
                    )['amount'];
                }

                $price += $totalPrice;
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des transports réservés
     * @param Trip $trip
     * @return float
     * @throws Exception
     */
    public function getReservedTransportsPrice(Trip $trip): float
    {
        $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);
        $price = 0;

        foreach ($trip->getTransports() as $transport) {
            if (!$transport->isPaid()) continue;

            $finalPrice = $transport->isPerPerson() ? $transport->getTotalPrice() * $trip->getTripTravelers()->count() : $transport->getTotalPrice();

            if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                $finalPrice = $this->currencyConverterService->convert(
                    $finalPrice,
                    $eurCurrency,
                    $trip->getCurrency(),
                    $transport->getConvertedAt() ?? $transport->getPurchaseDate()
                )['amount'];
            }

            $price += $finalPrice;
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des transports non réservés
     * @param Trip $trip
     * @return float
     * @throws Exception
     */
    public function getNonReservedTransportsPrice(Trip $trip): float
    {
        $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);
        $price = 0;

        foreach ($trip->getTransports() as $transport) {
            if ($transport->isPaid()) continue;

            $finalPrice = $transport->getTotalPrice();

            if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                $finalPrice = $this->currencyConverterService->convert(
                    $finalPrice,
                    $eurCurrency,
                    $trip->getCurrency(),
                    $transport->getConvertedAt() ?? $transport->getPurchaseDate()
                )['amount'];
            }

            $price += $finalPrice;
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des activités réservées
     * @param Trip $trip
     * @return float
     * @throws Exception
     */
    public function getReservedActivitiesPrice(Trip $trip): float
    {
        $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);
        $price = 0;

        foreach ($trip->getActivities() as $activity) {
            if ($activity->isBooked()) {
                $calculatedPrice = $activity->getOriginalCurrency()?->getCode() !== 'EUR'
                    ? $activity->getConvertedPrice()
                    : $activity->getOriginalPrice();

                if ($activity->isPerPerson()) {
                    $calculatedPrice = $calculatedPrice * $trip->getTripTravelers()->count();
                }

                if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                    $calculatedPrice = $this->currencyConverterService->convert(
                        $calculatedPrice,
                        $eurCurrency,
                        $trip->getCurrency(),
                        $activity->getConvertedAt() ?? $activity->getPurchaseDate()
                    )['amount'];
                }

                $price += $calculatedPrice;
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des activités non réservées
     * @param Trip $trip
     * @return float
     * @throws Exception
     */
    public function getNonReservedActivitiesPrice(Trip $trip): float
    {
        $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);
        $price = 0;

        foreach ($trip->getActivities() as $activity) {
            if (!$activity->isBooked()) {
                $calculatedPrice = $activity->getOriginalCurrency()?->getCode() !== 'EUR'
                    ? $activity->getConvertedPrice()
                    : $activity->getOriginalPrice();

                if ($activity->isPerPerson()) {
                    $calculatedPrice = $calculatedPrice * $trip->getTripTravelers()->count();
                }

                if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                    $calculatedPrice = $this->currencyConverterService->convert(
                        $calculatedPrice,
                        $eurCurrency,
                        $trip->getCurrency(),
                        $activity->getConvertedAt() ?? $activity->getPurchaseDate()
                    )['amount'];
                }

                $price += $calculatedPrice;
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des dépenses supplémentaires achetées
     * @param Trip $trip
     * @return float
     * @throws Exception
     */
    public function getReservedVariousExpensivePrice(Trip $trip): float
    {
        $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);
        $price = 0;

        foreach ($trip->getVariousExpensives() as $expensive) {
            if ($expensive->isPaid()) {
                $calculatedPrice = $expensive->getOriginalCurrency()?->getCode() !== 'EUR'
                    ? $expensive->getConvertedPrice()
                    : $expensive->getOriginalPrice();

                if ($expensive->isPerPerson()) {
                    $calculatedPrice = $calculatedPrice * $trip->getTripTravelers()->count();
                }

                if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                    $calculatedPrice = $this->currencyConverterService->convert(
                        $calculatedPrice,
                        $eurCurrency,
                        $trip->getCurrency(),
                        $expensive->getConvertedAt() ?? $expensive->getPurchaseDate()
                    )['amount'];
                }

                $price += $calculatedPrice;
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des dépenses supplémentaires non achetées
     * @param Trip $trip
     * @return float
     * @throws Exception
     */
    public function getNonReservedVariousExpensivePrice(Trip $trip): float
    {
        $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);
        $price = 0;

        foreach ($trip->getVariousExpensives() as $expensive) {
            if (!$expensive->isPaid()) {
                $calculatedPrice = $expensive->getOriginalCurrency()?->getCode() !== 'EUR'
                    ? $expensive->getConvertedPrice()
                    : $expensive->getOriginalPrice();

                if ($expensive->isPerPerson()) {
                    $calculatedPrice = $calculatedPrice * $trip->getTripTravelers()->count();
                }

                if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                    $calculatedPrice = $this->currencyConverterService->convert(
                        $calculatedPrice,
                        $eurCurrency,
                        $trip->getCurrency(),
                        $expensive->getConvertedAt() ?? $expensive->getPurchaseDate()
                    )['amount'];
                }

                $price += $calculatedPrice;
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des dépenses effectuées sur place
     * @param Trip $trip
     * @return float
     * @throws Exception
     */
    public function getOnSiteExpensePrice(Trip $trip): float
    {
        $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);
        $price = 0;

        foreach ($trip->getOnSiteExpenses() as $expense) {
            $calculatedPrice = $expense->getOriginalCurrency()?->getCode() !== 'EUR'
                ? $expense->getConvertedPrice()
                : $expense->getOriginalPrice();

            if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                $price += $this->currencyConverterService->convert(
                    $calculatedPrice,
                    $eurCurrency,
                    $trip->getCurrency()
                )['amount'];
            }
        }


        return round($price, 2);
    }

    /**
     * Retourne le montant des dépenses déjà payées et celles restantes à payer
     * @param Trip $trip
     * @return array
     * @throws Exception
     */
    public function getBudget(Trip $trip): array
    {
        $onSite = $this->getOnSiteExpensePrice($trip);

        $reservedPrices = [
            'accommodations' => [
                'title' => $this->translator->trans('trip.budget.accommodation'),
                'description' => $this->translator->trans('trip.budget.accommodation.desc'),
                'amount' => $this->getReservedAccommodationsPrice($trip)
            ],
            'transports' => [
                'title' => $this->translator->trans('trip.budget.transport'),
                'description' => $this->translator->trans('trip.budget.transport.desc'),
                'amount' => $this->getReservedTransportsPrice($trip)
            ],
            'activities' => [
                'title' => $this->translator->trans('trip.budget.activity'),
                'description' => $this->translator->trans('trip.budget.activity.desc'),
                'amount' => $this->getReservedActivitiesPrice($trip)
            ],
            'various-expensive' => [
                'title' => $this->translator->trans('trip.budget.various'),
                'description' => $this->translator->trans('trip.budget.various.desc'),
                'amount' => $this->getReservedVariousExpensivePrice($trip)
            ],
            'on-site' => [
                'title' => $this->translator->trans('trip.budget.on_site'),
                'description' => $this->translator->trans('trip.budget.on_site.desc'),
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
     * @throws Exception
     */
    public function getExpensesByTraveler(Trip $trip): array
    {
        $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);
        $balances = [];
        $totalTravelers = $trip->getTripTravelers()->count();

        if ($totalTravelers > 0) {
            $allTransports = $this->managerRegistry->getRepository(Transport::class)->findBy(['trip' => $trip]);

            foreach ($trip->getTripTravelers() as $traveler) {
                $accommodationsTotal = 0;
                $accommodations = $this->managerRegistry->getRepository(Accommodation::class)->findByTraveler($trip, $traveler);
                foreach ($accommodations as $accommodation) {
                    $calculatedPrice = $accommodation['priceTotal'];

                    if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                        $calculatedPrice = $this->currencyConverterService->convert(
                            $calculatedPrice,
                            $eurCurrency,
                            $trip->getCurrency(),
                            $accommodation['convertedAt'] ?? $accommodation['purchaseDate']
                        )['amount'];
                    }

                    $accommodationsTotal += $calculatedPrice;
                }

                $activitiesTotal = 0;
                $activities = $this->managerRegistry->getRepository(Activity::class)->findByTraveler($trip, $traveler);
                foreach ($activities as $activity) {
                    $calculatedPrice = $activity['priceTotal'];

                    if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                        $calculatedPrice = $this->currencyConverterService->convert(
                            $calculatedPrice,
                            $eurCurrency,
                            $trip->getCurrency(),
                            $activity['convertedAt'] ?? $activity['purchaseDate']
                        )['amount'];
                    }

                    $activitiesTotal += $calculatedPrice;
                }

                $variousExpensesTotal = 0;
                $variousExpenses = $this->managerRegistry->getRepository(VariousExpensive::class)->findByTraveler($trip, $traveler);
                foreach ($variousExpenses as $variousExpens) {
                    $calculatedPrice = $variousExpens['priceTotal'];

                    if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                        $calculatedPrice = $this->currencyConverterService->convert(
                            $calculatedPrice,
                            $eurCurrency,
                            $trip->getCurrency(),
                            $variousExpens['convertedAt'] ?? $variousExpens['purchaseDate']
                        )['amount'];
                    }

                    $variousExpensesTotal += $calculatedPrice;
                }

                $onSiteTotal = 0;
                $onSite = $this->managerRegistry->getRepository(OnSiteExpense::class)->findByTraveler($trip, $traveler);
                foreach ($onSite as $item) {
                    $calculatedPrice = $item['priceTotal'];

                    if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                        $calculatedPrice = $this->currencyConverterService->convert(
                            $calculatedPrice,
                            $eurCurrency,
                            $trip->getCurrency(),
                            $item['convertedAt'] ?? $item['purchaseDate']
                        )['amount'];
                    }

                    $onSiteTotal += $calculatedPrice;
                }

                $transportPaid = 0;
                foreach ($allTransports as $transport) {
                    if ($transport->isPaid()) {
                        $calculatedPrice = 0;

                        if ($transport->getType()->getName() === 'Voiture') {
                            $calculatedPrice = round(($transport->getEstimatedToll() + $transport->getEstimatedGasoline()) / $totalTravelers, 2);
                        } elseif ($transport->isPerPerson() && $transport->getPayedBy() === $traveler) {
                            if ($transport->getOriginalCurrency()->getCode() !== 'EUR') {
                                $calculatedPrice = round($transport->getConvertedPrice() * $totalTravelers, 2);
                            } else {
                                $calculatedPrice = round($transport->getOriginalPrice() * $totalTravelers, 2);
                            }
                        } elseif (!$transport->isPerPerson() && $transport->getPayedBy() === $traveler) {
                            if ($transport->getOriginalCurrency()->getCode() !== 'EUR') {
                                $calculatedPrice = $transport->getConvertedPrice();
                            } else {
                                $calculatedPrice = $transport->getOriginalPrice();
                            }
                        } elseif (!$transport->getPayedBy() && $transport->getType()->getName() === 'Transports en commun') {
                            if ($transport->isPerPerson()) {
                                if ($transport->getOriginalCurrency()->getCode() !== 'EUR') {
                                    $calculatedPrice = $transport->getConvertedPrice();
                                } else {
                                    $calculatedPrice = $transport->getOriginalPrice();
                                }
                            } else {
                                if ($transport->getOriginalCurrency()->getCode() !== 'EUR') {
                                    $calculatedPrice = round($transport->getConvertedPrice() / $totalTravelers, 2);
                                } else {
                                    $calculatedPrice = round($transport->getOriginalPrice() / $totalTravelers, 2);
                                }
                            }
                        }

                        if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                            $calculatedPrice = $this->currencyConverterService->convert(
                                $calculatedPrice,
                                $eurCurrency,
                                $trip->getCurrency(),
                                $transport->getConvertedAt() ?? $transport->getPurchaseDate()
                            )['amount'];
                        }

                        $transportPaid += $calculatedPrice;
                    }
                }

                $paid = round($accommodationsTotal + $transportPaid + $activitiesTotal + $variousExpensesTotal + $onSiteTotal, 2);

                $balances[$traveler->getName()] = [
                    'id' => $traveler->getId(),
                    'paypalHandle' => $traveler->getInvited()?->getPaypalHandle(),
                    'revolutHandle' => $traveler->getInvited()?->getRevolutHandle(),
                    'paid' => $paid,
                    'amountDue' => 0.0,
                    $this->translator->trans('trip.budget.accommodation') => round($accommodationsTotal, 2),
                    $this->translator->trans('trip.budget.transport') => round($transportPaid, 2),
                    $this->translator->trans('trip.budget.activity') => round($activitiesTotal, 2),
                    $this->translator->trans('trip.budget.various') => round($variousExpensesTotal, 2),
                    $this->translator->trans('trip.budget.on_site') => round($onSiteTotal, 2),
                ];
            }

            // Calcul de la part équitable depuis les totaux réels par voyageur (garantit la symétrie)
            $totalPaid = array_sum(array_column($balances, 'paid'));
            $amountByPerson = round($totalPaid / $totalTravelers, 2);
            foreach ($balances as &$balance) {
                $amountDue = round($amountByPerson - $balance['paid'], 2);
                $balance['amountDue'] = abs($amountDue) <= 0.01 ? 0.0 : $amountDue;
            }
            unset($balance);

            foreach ($trip->getReimbursements() as $reimbursement) {
                $fromName = $reimbursement->getFromTraveler()->getName();
                $toName = $reimbursement->getToTraveler()->getName();
                $amount = (float) $reimbursement->getAmount();

                if (isset($balances[$fromName])) {
                    $balances[$fromName]['paid'] += $amount;
                    $balances[$fromName]['amountDue'] -= $amount;
                }
                if (isset($balances[$toName])) {
                    $balances[$toName]['paid'] -= $amount;
                    $balances[$toName]['amountDue'] += $amount;
                }
            }

            foreach ($balances as $name => &$balance) {
                $balance['paid'] = round($balance['paid'], 2);
                $rawAmountDue = round($balance['amountDue'], 2);
                $balance['amountDue'] = abs($rawAmountDue) <= 0.01 ? 0.0 : $rawAmountDue;
            }
            unset($balance);
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
                'fromId' => $balances[$debtor]['id'] ?? null,
                'to' => $creditor,
                'toId' => $balances[$creditor]['id'] ?? null,
                'toPaypalHandle' => $balances[$creditor]['paypalHandle'] ?? null,
                'toRevolutHandle' => $balances[$creditor]['revolutHandle'] ?? null,
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
                return $this->translator->trans('trip.date.element_before_trip_start');
            }

            if ($departureDate && $end && $end < $departureDate) {
                return $this->translator->trans('trip.date.element_end_before_trip_start');
            }

            if (!$departureDate) {
                if ($start && $start < $today) {
                    return $this->translator->trans('trip.date.element_before_today_no_dates');
                }
                if ($end && $end < $today) {
                    return $this->translator->trans('trip.date.element_end_before_today_no_dates');
                }
            }

            if ($returnDate && $start && $start > $returnDate) {
                return $this->translator->trans('trip.date.element_after_trip_end');
            }

            if ($returnDate && $end && $end > $returnDate) {
                return $this->translator->trans('trip.date.element_end_after_trip_end');
            }

            if ($start && $end && $end < $start) {
                return $this->translator->trans('trip.date.element_end_before_start');
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
                ->from($this->fromMail)
                ->to($userToShareWith ? $userToShareWith->getEmail() : $mail)
                ->subject($this->translator->trans('mail.share.subject', ['%app_name%' => $this->appName]))
                ->htmlTemplate('trip/share-mail.html.twig')
                ->context([
                    'url' => $url,
                    'invitedBy' => $invitedBy,
                    'trip' => $trip,
                    'domain' => $this->domain,
                    'app_name' => $this->appName,
                ]);

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

    public function getDestinations(Trip $trip, $forExport = false): array
    {
        if ($forExport) {
            $cities = $this->weatherService->getCities($trip);
            $weatherByDestinations = $this->weatherService->getWeatherByDestinations($cities, $trip, $forExport);
        }

        $destinations = [];

        foreach ($trip->getDestinations() as $destination) {
            if ($forExport) {
                $getWeather = array_filter($weatherByDestinations, function ($weather) use ($destination) {
                    return $weather['destination']['country'] === $destination->getCountry()->getName();
                });
            }

            $destinations[] = [
                'id' => $destination->getId(),
                'displayOrder' => $destination->getDisplayOrder(),
                'country' => [
                    'id' => $destination->getCountry()->getId(),
                    'code' => $destination->getCountry()->getCode(),
                    'name' => $destination->getCountry()->getName(),
                ],
                'departureDate' => $destination->getDepartureDate()?->format('Y-m-d'),
                'returnDate' => $destination->getReturnDate()?->format('Y-m-d'),
                'weather' => $getWeather ?? null
            ];
        }

        usort($destinations, function ($a, $b) {
            return $a['displayOrder'] <=> $b['displayOrder'];
        });

        return $destinations;
    }

    public function formateDestinationsForString(array $destinations)
    {
        $and = $this->translator->trans('stats.destinations.and');

        if (count($destinations) === 1) {
            return $destinations[0]['country']['name'];
        } elseif (count($destinations) === 2) {
            return $destinations[0]['country']['name'] . $and . $destinations[1]['country']['name'];
        } else {
            return implode(', ', array_slice($destinations, 0, -1)) . $and . $destinations[count($destinations) - 1]['country']['name'];
        }
    }
}