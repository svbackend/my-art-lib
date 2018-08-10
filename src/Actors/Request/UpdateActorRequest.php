<?php

namespace App\Actors\Request;

use App\Actors\Entity\Actor;
use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateActorRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'actor' => new Assert\Collection([
                // Movie
                'originalName' => [new Assert\NotBlank(), new Assert\Length(['min' => 2, 'max' => 100])],
                'imdbId' => new Assert\Length(['min' => 5, 'max' => 20]),
                'birthday' => new Assert\Date(),
                'gender' => new Assert\Range([Actor::GENDER_MALE, Actor::GENDER_FEMALE]),
                // MovieTranslations[]
                'translations' => $this->eachItemValidation([
                    'locale' => [new Assert\NotBlank(), new Assert\Locale()],
                    'name' => [new Assert\NotBlank(), new Assert\Length(['min' => 2, 'max' => 100])],
                    'placeOfBirth' => [new Assert\NotBlank(), new Assert\Length(['max' => 100])],
                    'biography' => [new Assert\NotBlank()],
                ]),
            ]),
        ]);
    }
}
