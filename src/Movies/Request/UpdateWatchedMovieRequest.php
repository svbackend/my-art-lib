<?php

namespace App\Movies\Request;

use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateWatchedMovieRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'movie' => new Assert\Collection([
                'vote' => [new Assert\Range(['min' => 0.0, 'max' => 10.0])],
                'watchedAt' => [new Assert\Date()],
            ]),
        ]);
    }
}
