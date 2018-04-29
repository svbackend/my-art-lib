<?php

namespace App\Users\Controller;

use App\Controller\BaseController;
use App\Controller\ControllerInterface;
use App\Users\Entity\User;
use App\Users\Event\UserRegisteredEvent;
use App\Users\Repository\ConfirmationTokenRepository;
use App\Users\Repository\UserRepository;
use App\Users\Request\ConfirmEmailRequest;
use App\Users\Request\RegisterUserRequest;
use App\Users\Service\RegisterService;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends BaseController
{
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
     * @param $request \App\Users\Request\RegisterUserRequest
     * @return User|JsonResponse
     */
    public function postUsers(RegisterUserRequest $request, RegisterService $registerService, EventDispatcherInterface $dispatcher, ValidatorInterface $validator)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_ANONYMOUSLY');

        $registeredUser = $registerService->registerByRequest($request);
        $errors = $validator->validate($registeredUser);

        if ($errors && 0 !== $errors->count()) {
            return $request->getErrorResponse($errors);
        }

        $this->getDoctrine()->getManager()->flush();

        $userRegisteredEvent = new UserRegisteredEvent($registeredUser);
        $dispatcher->dispatch(UserRegisteredEvent::NAME, $userRegisteredEvent);

        return $this->response($registeredUser, 200, [], [
            'groups' => ['view'],
        ]);
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
     * @param $confirmationTokenRepository \App\Users\Repository\ConfirmationTokenRepository
     * @return JsonResponse
     */
    public function postConfirmEmail(ConfirmEmailRequest $request, ConfirmationTokenRepository $confirmationTokenRepository, TranslatorInterface $translator)
    {
        $token = $request->get('token');

        if (null === $confirmationToken = $confirmationTokenRepository->findByToken($token)) {
            throw new BadCredentialsException($translator->trans('bad_email_confirmation_token', [
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
    public function getUsers($id, TranslatorInterface $translator)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /**
         * @var $userRepository \App\Users\Repository\UserRepository
         */
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $user = $userRepository->find($id);

        if ($user === null) {
            throw new NotFoundHttpException($translator->trans('not_found_by_id', [
                'user_id' => $id,
            ], 'users'));
        }

        return $this->response($user, 200, [], [
            'groups' => ['view'],
        ]);
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

        return $this->response($users, 200, [], [
            'groups' => ['list'],
        ]);
    }
}