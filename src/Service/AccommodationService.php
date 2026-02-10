<?php

namespace App\Service;

use App\DTO\AccommodationAdditionalRequestDTO;
use App\DTO\AccommodationRequestDTO;
use App\Entity\Accommodation;
use App\Entity\AccommodationAdditional;
use App\Entity\Currency;
use App\Entity\Trip;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AccommodationService
{
    public function __construct(
        readonly ManagerRegistry    $managerRegistry,
        readonly ValidatorInterface $validator,
        readonly CurrencyConverterService $converterService,
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
                    $currencyAdditional = $this->managerRegistry->getRepository(Currency::class)
                        ->findOneBy(['code' => $additionalExpensive['originalCurrency']]);

                    $dtoAdditional = new AccommodationAdditionalRequestDTO();
                    $dtoAdditional->id = $additionalExpensive['id'] ?? null;
                    $dtoAdditional->name = $additionalExpensive['name'];
                    $dtoAdditional->originalPrice = $additionalExpensive['originalPrice'];
                    $dtoAdditional->originalCurrency = $currencyAdditional;

                    if ($dtoAdditional->id !== null) {
                        $sentIds[] = (int)$dtoAdditional->id;
                    }

                    $dto->additionalExpensive[] = $dtoAdditional;
                    $errors->addAll($this->validator->validate($dtoAdditional));
                }
            } elseif ($key === 'originalCurrency' || $key === 'originalDepositCurrency') {
                $currency = $this->managerRegistry->getRepository(Currency::class)
                    ->findOneBy(['code' => $value]);

                $dto->{$key} = $currency;
            } else {
                $dto->{$key} = $value;
            }
        }

        $errors->addAll($this->validator->validate($dto));

        return [$dto, $errors, $sentIds];
    }

    /**
     * @param Trip $trip
     * @param Accommodation $accommodation
     * @param AccommodationRequestDTO $dto
     * @param array $sentIds
     * @return void
     * @throws \Exception
     */
    public function handleAccommodationForm(Trip $trip, Accommodation $accommodation, AccommodationRequestDTO $dto, array $sentIds): void
    {
        $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);

        if ($dto->originalCurrency && $eurCurrency !== $dto->originalCurrency && $accommodation->getOriginalPrice() !== $dto->originalPrice) {
            $convertedDeposit = $this->converterService->convert($dto->originalPrice, $dto->originalCurrency, $eurCurrency);
            $accommodation->setConvertedPrice($convertedDeposit['amount']);
            $accommodation->setExchangeRate($convertedDeposit['rate']);
            $accommodation->setConvertedAt(new \DateTimeImmutable());
        }

        if ($dto->originalDeposit && $eurCurrency !== $dto->originalDepositCurrency && $accommodation->getOriginalDeposit() !== $dto->originalDeposit) {
            $convertedDeposit = $this->converterService->convert($dto->originalDeposit, $dto->originalDepositCurrency, $eurCurrency);
            $accommodation->setConvertedDeposit($convertedDeposit['amount']);
        }

        $accommodation
            ->setName($dto->name)
            ->setAddress($dto->address)
            ->setZipCode($dto->zipCode)
            ->setCity($dto->city)
            ->setCountry($dto->country)
            ->setArrivalDate($dto->arrivalDate)
            ->setDepartureDate($dto->departureDate)
            ->setDescription($dto->description)
            // TODO: à supprimer plus tard
            ->setPrice($dto->originalPrice)
            ->setOriginalPrice($dto->originalPrice)
            ->setOriginalCurrency($dto->originalCurrency)
            ->setOriginalDeposit($dto->originalDeposit)
            ->setOriginalDepositCurrency($dto->originalDepositCurrency)
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


            if ($eurCurrency !== $additionalExpensive->originalCurrency && $accommodationAdditional->getOriginalPrice() !== $additionalExpensive->originalPrice) {
                $convertedDeposit = $this->converterService->convert(
                    $additionalExpensive->originalPrice,
                    $additionalExpensive->originalCurrency,
                    $eurCurrency
                );

                $accommodationAdditional->setConvertedPrice($convertedDeposit['amount']);
                $accommodationAdditional->setExchangeRate($convertedDeposit['rate']);
                $accommodationAdditional->setConvertedAt(new \DateTimeImmutable());
            }

            $accommodationAdditional
                ->setName($additionalExpensive->name)
                // TODO: à supprimer plus tard
                ->setPrice($additionalExpensive->originalPrice)
                ->setOriginalPrice($additionalExpensive->originalPrice)
                ->setOriginalCurrency($additionalExpensive->originalCurrency);

            $this->managerRegistry->getManager()->persist($accommodationAdditional);
            $accommodation->addAdditionalExpensive($accommodationAdditional);
        }

        $this->managerRegistry->getManager()->persist($accommodation);
        $this->managerRegistry->getManager()->flush();
    }
}