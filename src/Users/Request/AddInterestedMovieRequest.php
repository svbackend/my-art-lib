<?php

namespace App\Users\Request;

use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;

class AddInterestedMovieRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'movie_id' => new Assert\NotBlank(),
        ]);
    }
}
