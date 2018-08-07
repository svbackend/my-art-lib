<?php

namespace App\Users\Request;

use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;

class PasswordLostRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'email' => new Assert\Email(),
        ]);
    }
}
