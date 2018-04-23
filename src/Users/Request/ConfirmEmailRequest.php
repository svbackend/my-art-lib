<?php

namespace App\Users\Request;

use Symfony\Component\Validator\Constraints as Assert;
use App\Request\BaseRequest;

class ConfirmEmailRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'token' => new Assert\Length(['min' => 32, 'max' => 64]),
        ]);
    }
}