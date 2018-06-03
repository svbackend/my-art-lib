<?php

namespace App\Users\Controller;

use App\Controller\BaseController;
use App\Users\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserCheckController extends BaseController
{
    /**
     * Check is user exists by username
     *
     * @Route("/api/users/username/{username}", methods={"GET"})
     * @param string $username
     * @param UserRepository $repository
     * @return JsonResponse
     */
    public function isUserExistsByUsername(string $username, UserRepository $repository)
    {
        $isUserExists = $repository->isUserExists([
            'username' => $username,
        ]);

        if ($isUserExists === true) {
            return new JsonResponse();
        }

        return new JsonResponse([], 404);
    }

    /**
     * Check is user exists by email
     *
     * @Route("/api/users/email/{email}", methods={"GET"})
     * @param string $email
     * @param UserRepository $repository
     * @return JsonResponse
     */
    public function isUserExistsByEmail(string $email, UserRepository $repository)
    {
        $isUserExists = $repository->isUserExists([
            'email' => $email,
        ]);

        if ($isUserExists === true) {
            return new JsonResponse();
        }

        return new JsonResponse([], 404);
    }
}