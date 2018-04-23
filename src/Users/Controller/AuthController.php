<?php

namespace App\Users\Controller;

use App\Controller\ControllerInterface;
use App\Users\Request\AuthUserRequest;
use App\Users\Service\AuthService;
use FOS\RestBundle\Controller\FOSRestController;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;

class AuthController extends FOSRestController implements ControllerInterface
{
    /**
     * Endpoint action to get Access Token for authentication.
     *
     * @Route("/api/auth/login", methods={"POST"});
     * @SWG\Parameter(
     *      name="credentials",
     *      in="formData",
     *      type="string",
     *      description="Credentials",
     *      required=true,
     *      @SWG\Schema(
     *          type="object",
     *          example={"username": "username", "password": "password"}
     *      )
     *  )
     * @SWG\Response(
     *      response=200,
     *      description="API Token for user",
     *      @SWG\Schema(
     *          type="object",
     *          example={"api_token": "_api_token_"},
     *          @SWG\Property(property="api_token", type="string", description="Api Access Token"),
     *      ),
     *  )
     * @SWG\Response(
     *      response=400,
     *      description="Bad Request",
     *      @SWG\Schema(
     *          type="object",
     *          example={"message": "message", "errors": "array of errors"},
     *          @SWG\Property(property="message", type="integer", description="Error description"),
     *          @SWG\Property(property="errors", type="array", description="Errors list", @SWG\Items),
     *      ),
     *  )
     * @SWG\Tag(name="Authentication")
     *
     * @throws \LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @param $authUserRequest AuthUserRequest
     * @param $authService AuthService
     * @return array
     */
    public function login(AuthUserRequest $authUserRequest, AuthService $authService)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_ANONYMOUSLY');

        $apiToken = $authService->getTokenByRequest($authUserRequest);

        return ['api_token' => $apiToken->getToken()];
    }
}