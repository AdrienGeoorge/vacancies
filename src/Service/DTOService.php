<?php

namespace App\Service;

use App\DTO\ActivityRequestDTO;
use App\DTO\TransportRequestDTO;
use App\DTO\VariousExpensiveRequestDTO;
use App\Entity\Activity;
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
        array                                                             $data,
        ActivityRequestDTO|TransportRequestDTO|VariousExpensiveRequestDTO &$dto
    ): ActivityRequestDTO|TransportRequestDTO|VariousExpensiveRequestDTO|array
    {
        $errors = new ConstraintViolationList();

        foreach ($data as $key => $value) {
            if ($key === 'selectedType') continue;

            if ($key === 'date' || $key === 'departureDate' || $key === 'arrivalDate') {
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
        ActivityRequestDTO|TransportRequestDTO|VariousExpensiveRequestDTO $dto,
        Activity|Transport|VariousExpensive                               $entity
    ): Activity|Transport|VariousExpensive
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