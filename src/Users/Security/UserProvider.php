<?php

declare(strict_types=1);

namespace App\Users\Security;

use App\Users\Entity\User;
use App\Users\Repository\ApiTokenRepository;
use App\Users\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class UserProvider implements UserProviderInterface
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var ApiTokenRepository
     */
    private $apiTokenRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        UserRepository $userRepository,
        ApiTokenRepository $apiTokenRepository,
        TranslatorInterface $translator
    ) {
        $this->userRepository = $userRepository;
        $this->apiTokenRepository = $apiTokenRepository;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username): User
    {
        $user = $this->userRepository->loadUserByUsername($username);

        if (!$user) {
            throw new UsernameNotFoundException($this->translator->trans('not_found_by_username', [
                'username' => $username,
            ], 'users'));
        }

        return $user;
    }

    /**
     * @param string $token
     *
     * @return User
     */
    public function loadUserByToken(string $token): User
    {
        $apiToken = $this->apiTokenRepository->findByToken($token);

        if ($apiToken === null) {
            throw new UsernameNotFoundException($this->translator->trans('not_found_by_api_token', [
                'token' => $token,
            ], 'users'));
        }

        return $apiToken->getUser();
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user): ?User
    {
        if (!$this->supportsClass(get_class($user))) {
            throw new UnsupportedUserException($this->translator->trans('unexpected_class', [
                'expected_class' => User::class,
                'actual_class' => get_class($user),
            ], 'exceptions'));
        }

        /** @var $user User */
        if (null === $reloadedUser = $this->userRepository->find($user->getId())) {
            throw new UsernameNotFoundException($this->translator->trans('user_provider.reload.error', [
                'user_id' => $user->getId(),
            ], 'exceptions'));
        }

        return $reloadedUser;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        $userClass = User::class;

        return $userClass === $class || is_subclass_of($class, $userClass);
    }
}
