<?php

namespace App\Users\Request;

use Symfony\Component\Validator\Constraints as Assert;
use App\Request\BaseRequest;

class MergeWatchedMoviesRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'token' => [new Assert\NotBlank()],
        ]);
    }
}