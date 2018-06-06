<?php

namespace App\Users\Request;

use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;

class ConfirmEmailRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'token' => new Assert\Length(['min' => 32, 'max' => 64]),
        ]);
    }
}
