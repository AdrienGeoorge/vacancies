<?php

namespace App\Service;

use App\DTO\AccommodationAdditionalRequestDTO;
use App\DTO\AccommodationRequestDTO;
use App\Entity\Accommodation;
use App\Entity\AccommodationAdditional;
use App\Entity\Trip;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AccommodationService
{
    public function __construct(
        readonly ManagerRegistry    $managerRegistry,
        readonly ValidatorInterface $validator
    )
    {
    }

    public function initDtoFromRequest(array $data): array
    {
        $errors = new ConstraintViolationList();
        $dto = new AccommodationRequestDTO();
        $sentIds = [];

        foreach ($data as $key => $value) {
            if ($key === 'arrivalDate' || $key === 'departureDate') {
                try {
                    $dto->{$key} = $value ? new \DateTime($value) : null;
                } catch (\Exception) {
                    $errors->add(new ConstraintViolation('La date est invalide.', '', [], null, $key, null));
                }
            } elseif ($key === 'additionalExpensive') {
                foreach ($value as $additionalExpensive) {
                    $dtoAdditional = new AccommodationAdditionalRequestDTO();
                    $dtoAdditional->id = $additionalExpensive['id'] ?? null;
                    $dtoAdditional->name = $additionalExpensive['name'];
                    $dtoAdditional->price = $additionalExpensive['price'];

                    if ($dtoAdditional->id !== null) {
                        $sentIds[] = (int)$dtoAdditional->id;
                    }

                    $dto->additionalExpensive[] = $dtoAdditional;
                    $errors->addAll($this->validator->validate($dtoAdditional));
                }
            } else {
                $dto->{$key} = $value;
            }
        }

        $errors->addAll($this->validator->validate($dto));

        return [$dto, $errors, $sentIds];
    }

    public function handleAccommodationForm(Trip $trip, Accommodation $accommodation, AccommodationRequestDTO $dto, array $sentIds): void
    {
        $accommodation
            ->setName($dto->name)
            ->setAddress($dto->address)
            ->setZipCode($dto->zipCode)
            ->setCity($dto->city)
            ->setCountry($dto->country)
            ->setArrivalDate($dto->arrivalDate)
            ->setDepartureDate($dto->departureDate)
            ->setDescription($dto->description)
            ->setPrice($dto->price)
            ->setDeposit($dto->deposit)
            ->setTrip($trip);

        foreach (clone $accommodation->getAdditionalExpensive() as $existingAdditional) {
            $existingId = $existingAdditional->getId();
            if ($existingId !== null && !in_array($existingId, $sentIds, true)) {
                $accommodation->removeAdditionalExpensive($existingAdditional);
            }
        }

        foreach ($dto->additionalExpensive as $additionalExpensive) {
            $accommodationAdditional = $additionalExpensive->id !== null
                ? $this->managerRegistry->getRepository(AccommodationAdditional::class)->find($additionalExpensive->id)
                : null;

            $accommodationAdditional ??= new AccommodationAdditional();

            $accommodationAdditional
                ->setName($additionalExpensive->name)
                ->setPrice($additionalExpensive->price);

            $this->managerRegistry->getManager()->persist($accommodationAdditional);
            $accommodation->addAdditionalExpensive($accommodationAdditional);
        }

        $this->managerRegistry->getManager()->persist($accommodation);
        $this->managerRegistry->getManager()->flush();
    }
}