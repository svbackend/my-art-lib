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
use Symfony\Component\Translation\TranslatorInterface;

class UserController extends FOSRestController
{
    /**
     * @var RegisterService
     */
    private $registerService;

    private $translator;

    public function __construct(RegisterService $registerService, TranslatorInterface $translator)
    {
        $this->registerService = $registerService;
        $this->translator = $translator;
    }

    /**
     * Registration
     *
     * @Route("/api/users", methods={"POST"})
     * @SWG\Parameter(name="registration.username", in="formData", type="string")
     * @SWG\Parameter(name="registration.password", in="formData", type="string")
     * @SWG\Parameter(name="registration.email", in="formData", type="string")
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
        throw new NotFoundHttpException($this->translator->trans('wrong_action', [], 'exceptions'));
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
            throw new NotFoundHttpException($this->translator->trans('not_found_by_id', [
                'user_id' => $id,
            ], 'users'));
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