<?php

namespace App\Request\User;

use Symfony\Component\Validator\Constraints as Assert;
use App\Request\BaseRequest;

class AuthUserRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'credentials' => new Assert\Collection([
                'username' => new Assert\Length(['min' => 4, 'max' => 50]),
                'password' => new Assert\Length(['min' => 4, 'max' => 4096]),
            ]),
        ]);
    }
}