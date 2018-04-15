<?php
declare(strict_types=1);

namespace App\Service\User;

use App\Entity\ApiToken;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Request\User\AuthUserRequest;
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
    )
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->translator = $translator;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function getTokenByRequest(AuthUserRequest $request): ApiToken
    {
        $credentials = $request->get('credentials');

        $user = $this->findUserByCredentials($credentials['username'], $credentials['password']);
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

        if ($user === null) {
            throw new BadCredentialsException(
                $this->translator->trans('user_with_this_username_not_exist', [
                    'username' => $username,
                ], 'users')
            );
        }

        if ($this->isPasswordValid($password, $user) === false) {
            throw new BadCredentialsException(
                $this->translator->trans('wrong_password', [], 'users')
            );
        }

        return $user;
    }

    private function isPasswordValid(string $password, User $user): bool
    {
        return $user->isPasswordValid($password, $this->passwordEncoder);
    }
}