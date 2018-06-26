<?php

namespace App\Users\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Translation\TranslatorInterface;

class TokenAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function supports(Request $request)
    {
        return $request->query->has('api_token') && !empty($request->query->get('api_token'));
    }

    public function getCredentials(Request $request)
    {
        return $request->query->get('api_token');
    }

    public function getUser($apiToken, UserProviderInterface $userProvider)
    {
        if (!$userProvider instanceof UserProvider) {
            throw new \InvalidArgumentException(
                $this->translator->trans('invalid_user_provider', [
                    'actual' => get_class($userProvider),
                ], 'exceptions')
            );
        }

        /*
         * @var $userProvider UserProvider
         */
        return $userProvider->loadUserByToken($apiToken);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        // check credentials - e.g. make sure the password is valid
        // no credential check is needed in this case

        // return true to cause authentication success
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            'error' => $this->translator->trans('api_token_authentication_failure', [], 'error'),
            'error_description' => $this->translator->trans('api_token_authentication_failure_description', [], 'error'),
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
            'error' => $this->translator->trans('api_token_authentication_required', [], 'error'),
            'error_description' => $this->translator->trans('api_token_authentication_required_description', [], 'error'),
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return false;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }
}
