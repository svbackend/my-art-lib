<?php

namespace App\Users\Controller;

use App\Controller\BaseController;
use App\Users\Entity\User;
use App\Users\Event\UserRegisteredEvent;
use App\Users\Repository\ConfirmationTokenRepository;
use App\Users\Repository\UserRepository;
use App\Users\Request\ConfirmEmailRequest;
use App\Users\Request\RegisterUserRequest;
use App\Users\Service\RegisterService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends BaseController
{
    /**
     * Registration
     *
     * @Route("/api/users", methods={"POST"})
     * @param RegisterUserRequest $request
     * @param RegisterService $registerService
     * @param EventDispatcherInterface $dispatcher
     * @param ValidatorInterface $validator
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function postUsers(RegisterUserRequest $request, RegisterService $registerService, EventDispatcherInterface $dispatcher, ValidatorInterface $validator)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_ANONYMOUSLY'); // todo (not working)

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
     * @param ConfirmEmailRequest $request
     * @param ConfirmationTokenRepository $confirmationTokenRepository
     * @param TranslatorInterface $translator
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
     * @param $id
     * @param TranslatorInterface $translator
     * @return JsonResponse
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