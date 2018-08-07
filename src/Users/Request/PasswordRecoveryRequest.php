<?php

namespace App\Users\Request;

use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;

class PasswordRecoveryRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'password' => [new Assert\NotBlank(), new Assert\Length(['min' => 4, 'max' => 4096])],
            'token' => [new Assert\NotBlank(), new Assert\Length(['min' => 16, 'max' => 256])],
        ]);
    }
}
