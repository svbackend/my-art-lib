<?php
declare(strict_types=1);

namespace App\Security;

use App\Repository\UserRepository;
use App\Entity\User;
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
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(UserRepository $userRepository, TranslatorInterface $translator)
    {
        $this->userRepository = $userRepository;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        $user = $this->findUser($username);

        if (!$user) {
            throw new UsernameNotFoundException($this->translator->trans('not_found_by_username', [
                'username' => $username,
            ], 'users'));
        }

        return $user;
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

        /**
         * @var $user User
         */
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

    /**
     * Finds a user by username.
     *
     * This method is meant to be an extension point for child classes.
     *
     * @param string $username
     *
     * @return User|null
     */
    protected function findUser($username)
    {
        return $this->userRepository->loadUserByUsername($username);
    }
}