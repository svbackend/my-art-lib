<?php

namespace App\Request\User;

use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Request\BaseRequest;

class RegisterUserRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'registration' => new Assert\Collection([
                'email' => new Assert\Email(),
                'password' => new Assert\Length(['min' => 4, 'max' => 4096]),
                'username' => new Assert\Length(['min' => 4, 'max' => 50]),
            ]),
        ]);
    }
}