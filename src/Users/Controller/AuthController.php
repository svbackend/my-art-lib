<?php

namespace App\Users\Controller;

use App\Controller\BaseController;
use App\Users\Request\AuthUserRequest;
use App\Users\Service\AuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends BaseController
{
    /**
     * Endpoint action to get Access Token for authentication.
     *
     * @Route("/api/auth/login", methods={"POST"});
     *
     * @throws \LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @param $authUserRequest AuthUserRequest
     * @param $authService AuthService
     * @return JsonResponse
     */
    public function login(AuthUserRequest $authUserRequest, AuthService $authService)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_ANONYMOUSLY');

        $apiToken = $authService->getTokenByRequest($authUserRequest);

        return $this->response(['api_token' => $apiToken->getToken()]);
    }
}