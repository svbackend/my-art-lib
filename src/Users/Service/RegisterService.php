<?php

namespace App\Users\Service;

use App\Users\Entity\User;
use App\Users\Request\RegisterUserRequest;

class RegisterService
{
    public function registerByRequest(RegisterUserRequest $request): User
    {
        $data = $request->get('registration');

        return $this->register($data);
    }

    public function register(array $userData): User
    {
        return $this->createUserInstance($userData['email'], $userData['username'], $userData['password']);
    }

    /**
     * @param $email
     * @param $username
     * @param $password
     *
     * @return User
     */
    private function createUserInstance($email, $username, $password): User
    {
        return new User($email, $username, $password);
    }
}
