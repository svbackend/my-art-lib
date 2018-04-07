<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Request\User\RegisterUserRequest;
use App\Service\User\RegisterService;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;

class UserController extends FOSRestController
{
    /**
     * @var RegisterService
     */
    protected $registerService;

    public function __construct(RegisterService $registerService)
    {
        $this->registerService = $registerService;
    }

    /**
     * Registration
     *
     * @Route("/api/users", methods={"POST"})
     * @SWG\Parameter(name="username", in="formData", type="string")
     * @SWG\Parameter(name="password", in="formData", type="string")
     * @SWG\Parameter(name="email", in="formData", type="string")
     * @SWG\Response(
     *     description="Registration.",
     *     response=202,
     *     @Model(type=User::class)
     * )
     * @param $request RegisterUserRequest
     * @return User
     */
    public function postUsers(RegisterUserRequest $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_ANONYMOUSLY');

        return $this->registerService->registerByRequest($request);
    }

    /**
     * Authentication
     *
     * @Route("/oauth/v2/token", methods={"POST"})
     * @SWG\Parameter(name="username", in="formData", type="string")
     * @SWG\Parameter(name="password", in="formData", type="string")
     * @SWG\Response(
     *     description="Authentication.",
     *     response=202
     * )
     */
    public function login()
    {
        throw new NotFoundHttpException('This action should not be called!');
    }

    /**
     * Get single user
     *
     * @Route("/api/users/{id}", methods={"GET"})
     * @SWG\Response(
     *     description="REST action which returns user by id.",
     *     response=200,
     *     @Model(type=User::class)
     * )
     *
     * @param int $id
     * @return User
     */
    public function getUsers($id)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /**
         * @var $userRepository UserRepository
         */
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $user = $userRepository->find($id);

        if ($user === null) {
            throw new NotFoundHttpException(sprintf('The resource \'%s\' was not found.', $id));
        }

        return $user;
    }

    /**
     * Get all users
     *
     * @Route("/api/users", methods={"GET"})
     * @SWG\Response(
     *     description="REST action which returns user by id.",
     *     response=201,
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=User::class, groups={"full"}))
     *     )
     * )
     *
     * @return User[]
     */
    public function getAll()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /**
         * @var $userRepository UserRepository
         */
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $users = $userRepository->findAll();

        return $users;
    }
}