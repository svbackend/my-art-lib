<?php

namespace App\Users\Request;

use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'profile' => new Assert\Collection([
                'first_name' => new Assert\Length(['min' => 2, 'max' => 50]),
                'last_name' => new Assert\Length(['min' => 2, 'max' => 50]),
                'birth_date' => new Assert\Date(),
                'about' => new Assert\Length(['max' => 255]),
                'public_email' => new Assert\Email(),
                'country_code' => new Assert\Length(['min' => 3, 'max' => 3]),
            ]),
        ]);
    }
}
