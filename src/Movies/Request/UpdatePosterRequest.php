<?php

namespace App\Movies\Request;

use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;

class UpdatePosterRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'url' => [new Assert\NotBlank(), new Assert\Url()],
        ]);
    }
}
