<?php

namespace App\Movies\Request;

use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;

class NewMovieReviewRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'review' => new Assert\Collection([
                'text' => [new Assert\NotBlank(), new Assert\Length(['min' => 10])],
            ]),
        ]);
    }
}
