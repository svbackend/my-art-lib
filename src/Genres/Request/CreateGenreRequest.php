<?php

namespace App\Genres\Request;

use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;

class CreateGenreRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'genre' => new Assert\Collection([
                'translations' => $this->eachItemValidation([
                    'name' => new Assert\Length(['min' => 3, 'max' => 50]),
                    'locale' => new Assert\Locale(),
                ]),
            ]),
        ]);
    }
}
