<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;


class LuckyController extends FOSRestController
{
    public function getUsers()
    {
        $users = $this->getDoctrine()->getRepository(User::class)->findAll();

        return $users;
    }
}