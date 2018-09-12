<?php

namespace App\Users\Request;

use App\Movies\DTO\WatchedMovieDTO;
use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class AddInterestedMovieRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'movie_id' => new Assert\NotBlank(),
        ]);
    }
}
