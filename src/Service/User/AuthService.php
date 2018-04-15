<?php

namespace App\Service\User;

use App\Entity\ApiToken;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Request\User\AuthUserRequest;
use App\Request\User\RegisterUserRequest;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolation;

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

    public function getTokenByRequest(AuthUserRequest $request)
    {
        $credentials = $request->get('credentials');
        $username = $credentials['username'];
        $password = $credentials['password'];

        $user = $this->userRepository->loadUserByUsername($username);

        if ($user === null || $this->isPasswordValid($password, $user) === false) {
            // todo how to improve this? I don't want to create by my own similar errors
            $errors = new ConstraintViolationList([
                new ConstraintViolation(
                    $this->translator->trans('not_found_by_username_and_password', ['username' => $username], 'users'),
                    '', [], '', '[credentials][username]', $username
                ),
            ]);

            return $request->getErrorResponse($errors);
        }

        $apiToken = new ApiToken($user);

        $this->entityManager->persist($apiToken);
        $this->entityManager->flush();

        return ['api_token' => (string)$apiToken];
    }

    private function isPasswordValid(string $password, User $user)
    {
        return $user->isPasswordValid($password, $this->passwordEncoder);
    }
}