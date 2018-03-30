<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class PasswordAuthenticator extends AbstractGuardAuthenticator
{
    const USERNAME_KEY = 'username';
    const PASSWORD_KEY = 'password';

    protected $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function supports(Request $request)
    {
        return $request->request->has(self::USERNAME_KEY) && $request->request->has(self::PASSWORD_KEY);
    }

    public function getCredentials(Request $request)
    {
        return [
            self::USERNAME_KEY => $request->request->get(self::USERNAME_KEY),
            self::PASSWORD_KEY => $request->request->get(self::PASSWORD_KEY),
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        extract($credentials);

        if (null === $username || null === $password) {
            return;
        }

        // if a User object, checkCredentials() is called
        return $userProvider->loadUserByUsername($username);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        if ($user instanceof User) {
            return $user->isPasswordValid($credentials[self::PASSWORD_KEY], $this->passwordEncoder);
        }

        return false;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $data = [
            TokenAuthenticator::QUERY_TOKEN_KEY => (string)$token,
            TokenAuthenticator::HEADER_TOKEN_KEY => (string)$token,
            'providedKey' => $providerKey,
        ];

        $token->serialize();

        $data = [
            TokenAuthenticator::QUERY_TOKEN_KEY => (string)$token,
            TokenAuthenticator::HEADER_TOKEN_KEY => (string)$token,
            'providedKey' => $providerKey,
        ];

        return new JsonResponse($data, Response::HTTP_ACCEPTED);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())

            // or to translate this message
            // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
            // you might translate this message
            'message' => 'Authentication Required'
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}