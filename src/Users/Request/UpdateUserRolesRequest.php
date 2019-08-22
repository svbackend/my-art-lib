<?php

namespace App\Users\Request;

use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserRolesRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'roles' => [],
        ]);
    }
}
