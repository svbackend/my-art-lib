<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ConfirmationTokenRepository;
use App\Repository\UserRepository;
use App\Request\User\ConfirmEmailRequest;
use App\Request\User\RegisterUserRequest;
use App\Service\User\RegisterService;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
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
     * Confirm email
     *
     * @Route("/api/confirmEmail", methods={"POST"})
     * @SWG\Parameter(name="token", in="formData", type="string")
     * @SWG\Response(
     *     description="Email confirmed.",
     *     response=202
     * )
     * @param $request ConfirmEmailRequest
     * @param $confirmationTokenRepository ConfirmationTokenRepository
     * @return JsonResponse
     */
    public function postConfirmEmail(ConfirmEmailRequest $request, ConfirmationTokenRepository $confirmationTokenRepository)
    {
        $token = $request->get('token');

        if (null === $confirmationToken = $confirmationTokenRepository->findOneOrNullByToken($token)) {
            throw new BadCredentialsException($this->translator->trans('bad_email_confirmation_token', [
                'token' => $token,
            ], 'users'));
        }

        $user = $confirmationToken->getUser();
        $user->confirmEmail();

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->remove($confirmationToken);
        $entityManager->flush();

        return new JsonResponse(null, 202);
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
     *     description="REST action which returns all users.",
     *     response=200,
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