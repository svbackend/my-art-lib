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
use Doctrine\ORM\EntityManagerInterface;

class PasswordAuthenticator extends AbstractGuardAuthenticator
{
    const USERNAME_KEY = 'username';
    const PASSWORD_KEY = 'password';

    protected $passwordEncoder;
    protected $em;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, EntityManagerInterface $em)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->em = $em;
    }

    public function supports(Request $request)
    {
        return $request->getPathInfo() === '/api/login' && $request->request->has(self::USERNAME_KEY) && $request->request->has(self::PASSWORD_KEY);
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
        $username = $password = null;
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
        /**
         * @var $user User
         */
        $user = $token->getUser();
        $user->generateApiKey(); // <= bin2hex(openssl_random_pseudo_bytes(32));
        $this->em->persist($user);
        $this->em->flush();
        //todo - store api keys in another table with relation many (tokens) to one (user)

        // these tokens (below) client mast send with every request in headers or query
        // (/api/users/1?apiKey=...)
        $data = [
            TokenAuthenticator::QUERY_TOKEN_KEY => $user->getApiKey(),
            TokenAuthenticator::HEADER_TOKEN_KEY => $user->getApiKey(),
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