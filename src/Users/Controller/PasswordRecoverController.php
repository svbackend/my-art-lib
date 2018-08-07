<?php

namespace App\Users\Controller;

use App\Controller\BaseController;
use App\Users\Repository\ApiTokenRepository;
use App\Users\Repository\ConfirmationTokenRepository;
use App\Users\Repository\UserRepository;
use App\Users\Request\PasswordLostRequest;
use App\Users\Request\PasswordRecoveryRequest;
use App\Users\Service\SendEmailService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class PasswordRecoverController extends BaseController
{
    /**
     * @Route("/api/passwordLostRequest", methods={"POST"});
     *
     * @param PasswordLostRequest $request
     * @param UserRepository $repository
     * @param SendEmailService $sendEmailService
     *
     * @return JsonResponse
     */
    public function passwordLost(PasswordLostRequest $request, UserRepository $repository, SendEmailService $sendEmailService)
    {
        if ($this->getUser() !== null) {
            throw new AccessDeniedHttpException();
        }

        if (null === $user = $repository->findOneBy([
            'email' => $request->get('email')
            ])
        ) {
            throw new NotFoundHttpException();
        }

        $sendEmailService->sendPasswordRecoveryConfirmation($user);

        return new JsonResponse();
    }
    /**
     * @Route("/api/passwordRecovery", methods={"POST"});
     *
     * @param PasswordRecoveryRequest $request
     * @param ConfirmationTokenRepository $tokenRepository
     * @param ApiTokenRepository $apiTokenRepository
     *
     * @return JsonResponse
     */
    public function passwordRecovery(PasswordRecoveryRequest $request, ConfirmationTokenRepository $tokenRepository, ApiTokenRepository $apiTokenRepository)
    {
        if ($this->getUser() !== null) {
            throw new AccessDeniedHttpException();
        }

        if (null === $token = $tokenRepository->findByToken($request->get('token'))) {
            throw new NotFoundHttpException();
        }

        $user = $token->getUser();
        $user->setPlainPassword($request->get('password'));

        $oldApiTokens = $apiTokenRepository->findAllByUser($user->getId());

        $em = $this->getDoctrine()->getManager();
        $em->remove($token);

        foreach ($oldApiTokens as $oldApiToken) {
            $em->remove($oldApiToken);
        }

        $em->flush();

        return new JsonResponse();
    }
}
