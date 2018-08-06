<?php

namespace App\Users\Controller;

use App\Controller\BaseController;
use App\Users\Entity\User;
use App\Users\Entity\UserRoles;
use App\Users\Event\UserRegisteredEvent;
use App\Users\Repository\ConfirmationTokenRepository;
use App\Users\Repository\UserRepository;
use App\Users\Request\ConfirmEmailRequest;
use App\Users\Request\RegisterUserRequest;
use App\Users\Request\UpdateUserRequest;
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
     * Registration.
     *
     * @Route("/api/users", methods={"POST"})
     *
     * @param RegisterUserRequest      $request
     * @param RegisterService          $registerService
     * @param EventDispatcherInterface $dispatcher
     * @param ValidatorInterface       $validator
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function postUsers(RegisterUserRequest $request, RegisterService $registerService, EventDispatcherInterface $dispatcher, ValidatorInterface $validator)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_ANONYMOUSLY'); // todo (not working)

        $registeredUser = $registerService->registerByRequest($request);
        $errors = $validator->validate($registeredUser);

        if ($errors && $errors->count() !== 0) {
            return $request->getErrorResponse($errors);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($registeredUser);
        $em->flush();

        $userRegisteredEvent = new UserRegisteredEvent($registeredUser);
        $dispatcher->dispatch(UserRegisteredEvent::NAME, $userRegisteredEvent);

        return $this->response($registeredUser, 200, [], [
            'groups' => ['view'],
        ]);
    }

    /**
     * Confirm email.
     *
     * @Route("/api/confirmEmail", methods={"POST"})
     *
     * @param ConfirmEmailRequest         $request
     * @param ConfirmationTokenRepository $confirmationTokenRepository
     * @param TranslatorInterface         $translator
     *
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
     * Get single user.
     *
     * @Route("/api/users/{id}", methods={"GET"})
     *
     * @param $id
     * @param TranslatorInterface $translator
     *
     * @return JsonResponse
     */
    public function getUsers($id, TranslatorInterface $translator)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var $userRepository \App\Users\Repository\UserRepository */
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
     * @Route("/api/users/{id}", methods={"POST", "PUT", "PATCH"})
     *
     * @param User $user
     * @param UpdateUserRequest $request
     * @throws \Exception
     *
     * @return JsonResponse
     */
    public function putUsers(User $user, UpdateUserRequest $request)
    {
        $currentUser = $this->getUser();
        if ($currentUser === null) {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        }

        /** @var $currentUser User */
        if ($currentUser->getId() !== $user->getId()) {
            $this->denyAccessUnlessGranted(UserRoles::ROLE_ADMIN);
        }

        $profile = $user->getProfile();
        $profileData = $request->get('profile');
        $profile->setFirstName($profileData['first_name']);
        $profile->setLastName($profileData['last_name']);
        $profile->setBirthDate(new \DateTimeImmutable($profileData['birth_date']));
        $profile->setAbout($profileData['about']);
        $profile->setPublicEmail($profileData['public_email']);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(null, 202);
    }

    /**
     * Get all users.
     *
     * @Route("/api/users", methods={"GET"})
     */
    public function getAll()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /**
         * @var UserRepository
         */
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $users = $userRepository->findAll();

        return $this->response($users, 200, [], [
            'groups' => ['list'],
        ]);
    }
}
