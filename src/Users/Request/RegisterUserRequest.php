<?php

namespace App\Users\Request;

use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;

class RegisterUserRequest extends BaseRequest
{
    public function rules()
    {
        // maybe move common rules to "RuleSets" to prevent code duplication? (post-lunch)
        return new Assert\Collection([
            'registration' => new Assert\Collection([
                'email' => new Assert\Email(),
                'password' => new Assert\Length(['min' => 4, 'max' => 4096]),
                'username' => new Assert\Length(['min' => 4, 'max' => 50]),
            ]),
        ]);
    }
}
