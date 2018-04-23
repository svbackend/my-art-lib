<?php

namespace App\Users\Service;

use App\Users\Entity\User;
use App\Users\Event\UserRegisteredEvent;
use App\Users\Request\RegisterUserRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

// todo REFACTORING
// 1. Create user instance not here, use another service (or DTO?)
// 2. How to use custom errorHandler?
// 3. Save entity not here, looks like I need to move this to repository
class RegisterService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        EventDispatcherInterface $dispatcher
    )
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->dispatcher = $dispatcher;
    }

    public function registerByRequest(\App\Users\Request\RegisterUserRequest $request)
    {
        $data = $request->get('registration');

        $user = $this->createUserInstance($data['email'], $data['username'], $data['password']);

        $errors = $this->validator->validate($user);

        if ($errors && 0 !== $errors->count()) {
            return $request->getErrorResponse($errors);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $userRegisteredEvent = new UserRegisteredEvent($user);
        $this->dispatcher->dispatch(UserRegisteredEvent::NAME, $userRegisteredEvent);

        return $user;
    }

    /**
     * @param $email
     * @param $username
     * @param $password
     * @return User
     */
    private function createUserInstance($email, $username, $password): User
    {
        $user = new User();
        $user->email = $email;
        $user->username = $username;
        $user->setPlainPassword($password);

        return $user;
    }
}