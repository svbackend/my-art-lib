<?php

namespace App\Request;

use Fesor\RequestObject\RequestObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fesor\RequestObject\ErrorResponseProvider;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\PropertyAccess\PropertyAccess;

class BaseRequest extends RequestObject implements ErrorResponseProvider
{
    /**
     * {@inheritdoc}
     */
    public function getErrorResponse(ConstraintViolationListInterface $errors)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        return new JsonResponse([
            'message' => 'Please check your data',
            'errors' => array_map(function (ConstraintViolation $violation) use ($propertyAccessor) {
                return [
                    'path' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }, iterator_to_array($errors)),
        ], 400);
    }
}