<?php

// src/Security/PostVoter.php
namespace App\Security;

use App\Entity\Trip;
use App\Entity\TripSharing;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TripVoter extends Voter
{
    const VIEW = 'view';
    const EDIT_TRIP = 'edit_trip';
    const DELETE_TRIP = 'delete_trip';
    const EDIT_ELEMENTS = 'edit_elements';
    const INVITE = 'invite';

    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT_TRIP, self::DELETE_TRIP, self::EDIT_ELEMENTS, self::INVITE])) {
            return false;
        }

        if (!$subject instanceof Trip) return false;

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) return false;

        /** @var Trip $trip */
        $trip = $subject;

        // Cas de la création d'un nouveau voyage
        if ($trip->getId() === null) return true;

        return match ($attribute) {
            self::VIEW, self::EDIT_ELEMENTS => $this->canViewOrEditElements($trip, $user),
            self::EDIT_TRIP, self::DELETE_TRIP, self::INVITE => $this->canManageTripOrInvite($trip, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canViewOrEditElements(Trip $trip, User $user): bool
    {
        if ($this->canManageTripOrInvite($trip, $user)) return true;

        $canAccess = $this->managerRegistry->getRepository(TripSharing::class)
            ->findOneBy(['user' => $user, 'trip' => $trip]);

        if ($canAccess) return true;
        return false;
    }

    private function canManageTripOrInvite(Trip $trip, User $user): bool
    {
        return $user === $trip->getTraveler();
    }
}