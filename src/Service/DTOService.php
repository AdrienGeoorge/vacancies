<?php

namespace App\Service;

use App\DTO\ActivityRequestDTO;
use App\DTO\EventRequestDTO;
use App\DTO\OnSiteExpenseRequestDTO;
use App\DTO\TransportRequestDTO;
use App\DTO\VariousExpensiveRequestDTO;
use App\Entity\Activity;
use App\Entity\OnSiteExpense;
use App\Entity\PlanningEvent;
use App\Entity\Transport;
use App\Entity\VariousExpensive;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DTOService
{
    public function __construct(readonly ValidatorInterface $validator)
    {
    }

    public function initDto(
        array                                                                                                     $data,
        ActivityRequestDTO|TransportRequestDTO|VariousExpensiveRequestDTO|OnSiteExpenseRequestDTO|EventRequestDTO &$dto
    ): ActivityRequestDTO|TransportRequestDTO|VariousExpensiveRequestDTO|OnSiteExpenseRequestDTO|EventRequestDTO|array
    {
        $errors = new ConstraintViolationList();

        foreach ($data as $key => $value) {
            if ($key === 'type' || $key === 'payedBy' || $key === 'originalCurrency') continue;

            if ($key === 'date' || $key === 'departureDate' || $key === 'arrivalDate' || $key === 'purchaseDate' || $key === 'start' || $key === 'end') {
                try {
                    $dto->{$key} = $value ? new \DateTime($value) : null;
                } catch (\Exception) {
                    $errors->add(new ConstraintViolation('La date est invalide.', '', [], null, $key, null));
                }
            } else {
                $dto->{$key} = $value;
            }
        }

        $errors->addAll($this->validator->validate($dto));

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                return ['error' => [['message' => $error->getMessage()], 400]];
            }
        }

        return $dto;
    }

    public function mapToEntity(
        ActivityRequestDTO|TransportRequestDTO|VariousExpensiveRequestDTO|OnSiteExpenseRequestDTO|EventRequestDTO $dto,
        Activity|Transport|VariousExpensive|OnSiteExpense|PlanningEvent                                           $entity
    ): Activity|Transport|VariousExpensive|OnSiteExpense|PlanningEvent
    {
        foreach (get_object_vars($dto) as $property => $value) {
            $setter = 'set' . ucfirst($property);
            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
            }
        }

        return $entity;
    }
}