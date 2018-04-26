<?php

namespace App\Movies\Validation;

use Symfony\Component\Validator\Constraints as Assert;

class MovieTranslationRules
{
    public static function getDefaultRules(): array
    {
        return [
            'locale' => [new Assert\NotBlank(), new Assert\Locale()],
            'title' => [new Assert\NotBlank(), new Assert\Length(['min' => 3, 'max' => 50])],
            'posterUrl' => [new Assert\NotBlank(), new Assert\Length(['min' => 10, 'max' => 255])],
            'overview' => [new Assert\NotBlank(), new Assert\Length(['min' => 50])],
        ];
    }
}