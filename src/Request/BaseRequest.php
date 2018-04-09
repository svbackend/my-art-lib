<?php

namespace App\Request;

use Fesor\RequestObject\RequestObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fesor\RequestObject\ErrorResponseProvider;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\PropertyAccess\PropertyAccess;

class BaseRequest extends RequestObject implements ErrorResponseProvider
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getErrorResponse(ConstraintViolationListInterface $errors)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        return new JsonResponse([
            'message' => $this->translator->trans('invalid_data', [], 'validation'),
            'errors' => array_map(function (ConstraintViolation $violation) use ($propertyAccessor) {
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
}