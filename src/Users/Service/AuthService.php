<?php

declare(strict_types=1);

namespace App\Users\Service;

use App\Users\Entity\ApiToken;
use App\Users\Entity\User;
use App\Users\Repository\UserRepository;
use App\Users\Request\AuthUserRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Translation\TranslatorInterface;

class AuthService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        TranslatorInterface $translator,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->translator = $translator;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function getTokenByRequest(AuthUserRequest $request): ApiToken
    {
        $credentials = $request->get('credentials');

        return $this->getTokenByCredentials($credentials['username'], $credentials['password']);
    }

    public function getTokenByCredentials(string $username, string $password): ApiToken
    {
        $user = $this->findUserByCredentials($username, $password);
        $apiToken = $this->createApiTokenForUser($user);

        return $apiToken;
    }

    private function createApiTokenForUser(User $user): ApiToken
    {
        $apiToken = new ApiToken($user);

        $this->entityManager->persist($apiToken);
        $this->entityManager->flush();

        return $apiToken;
    }

    private function findUserByCredentials(string $username, string $password): User
    {
        $user = $this->userRepository->loadUserByUsername($username);

        if (null === $user) {
            throw new BadCredentialsException(
                $this->translator->trans('user_with_this_username_not_exist', [
                    'username' => $username,
                ], 'users')
            );
        }

        if (false === $this->passwordEncoder->isPasswordValid($user, $password)) {
            throw new BadCredentialsException(
                $this->translator->trans('wrong_password', [], 'users')
            );
        }

        return $user;
    }
}
