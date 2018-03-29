<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class IndexController
{
    public function index()
    {
        $number = mt_rand(0, 100);

        return new JsonResponse(['lucky_number' => $number]);
    }
}