<?php

namespace App\Users\Request;

use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'old_password' => new Assert\Length(['min' => 4, 'max' => 4096]),
            'new_password' => new Assert\Length(['min' => 4, 'max' => 4096]),
        ]);
    }
}
