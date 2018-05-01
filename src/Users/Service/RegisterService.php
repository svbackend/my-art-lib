<?php

namespace App\Users\Service;

use App\Users\Entity\User;
use App\Users\Request\RegisterUserRequest;
use Doctrine\ORM\EntityManagerInterface;

class RegisterService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function registerByRequest(RegisterUserRequest $request): User
    {
        $data = $request->get('registration');
        return $this->register($data);
    }

    public function register(array $userData): User
    {
        $user = $this->createUserInstance($userData['email'], $userData['username'], $userData['password']);
        $this->entityManager->persist($user);

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
        return new User($email, $username, $password);
    }
}