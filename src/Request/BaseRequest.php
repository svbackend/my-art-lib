<?php

namespace App\Request;

use Fesor\RequestObject\ErrorResponseProvider;
use Fesor\RequestObject\RequestObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

//todo How to inject TranslatorInterface ?
class BaseRequest extends RequestObject implements ErrorResponseProvider
{
    /**
     * {@inheritdoc}
     */
    public function getErrorResponse(ConstraintViolationListInterface $errors)
    {
        return new JsonResponse([
            'message' => 'Validation error. Please check your data.', //todo translate this message
            'errors' => array_map(function (ConstraintViolation $violation) {
                // todo find the way to show correct path to property.
                // Assert\* will return path like "[registration][username]"
                // But UniqueEntity will return path like "username"
                return [
                    'path' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }, iterator_to_array($errors)),
        ], 400);
    }

    public function eachItemValidation(array $constraints)
    {
        return new Assert\All([new Assert\Collection($constraints)]);
    }
}
