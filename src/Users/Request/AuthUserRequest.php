<?php

namespace App\Users\Request;

use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;

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
