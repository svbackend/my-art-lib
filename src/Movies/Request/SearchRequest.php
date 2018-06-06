<?php

namespace App\Movies\Request;

use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;

class SearchRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'query' => [new Assert\NotBlank(), new Assert\Length(['min' => 2, 'max' => 100])],
        ]);
    }
}
