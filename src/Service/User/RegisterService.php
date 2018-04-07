<?php

namespace App\Service\User;

use App\Entity\User;
use App\Request\User\RegisterUserRequest;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegisterService
{
    private $user;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(
        UserPasswordEncoderInterface $passwordEncoder,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    )
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    public function registerByRequest(RegisterUserRequest $request)
    {
        $data = $request->get('registration');

        $user = $this->createUserInstance($data['email'], $data['username'], $data['password']);

        $errors = $this->validator->validate($user);

        if ($errors && 0 !== $errors->count()) {
            return $request->getErrorResponse($errors);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * @param $email
     * @param $username
     * @param $password
     * @return User
     */
    private function createUserInstance($email, $username, $password)
    {
        $this->user = new User();
        $this->user->email = $email;
        $this->user->username = $username;
        $this->user->setPassword($password, $this->passwordEncoder);

        return $this->user;
    }
}