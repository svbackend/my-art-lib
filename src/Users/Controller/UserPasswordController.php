<?php

namespace App\Users\Controller;

use App\Controller\BaseController;
use App\Users\Entity\ConfirmationToken;
use App\Users\Entity\User;
use App\Users\Repository\ApiTokenRepository;
use App\Users\Repository\ConfirmationTokenRepository;
use App\Users\Repository\UserRepository;
use App\Users\Request\ChangePasswordRequest;
use App\Users\Request\PasswordRecoveryRequest;
use App\Users\Service\SendEmailService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserPasswordController extends BaseController
{
    /**
     * @Route("/api/users/{id}/password", methods={"POST"}, requirements={"id"="\d+"})
     *
     * @param ChangePasswordRequest        $request
     * @param User                         $user
     * @param UserPasswordEncoderInterface $passwordEncoder
     *
     * @return JsonResponse
     */
    public function postUserPassword(ChangePasswordRequest $request, User $user, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($user->getId() !== $this->getUser()->getId()) {
            throw new AccessDeniedHttpException();
        }

        if ($user->isPasswordValid($request->get('old_password'), $passwordEncoder) === false) {
            throw new BadRequestHttpException();
        }

        $user->setPlainPassword($request->get('new_password'));
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse();
    }

    /**
     * @Route("/api/users/{email}/recoverPassword", methods={"GET"})
     */
    public function getRecoverPassword(string $email, UserRepository $users, ConfirmationTokenRepository $tokens, SendEmailService $emailService)
    {
        if (null === $user = $users->loadUserByEmail($email)) {
            throw new NotFoundHttpException();
        }

        if ($this->getUser() !== null) {
            throw new AccessDeniedHttpException();
        }

        $token = $tokens->findByUserAndType($user, ConfirmationToken::TYPE_PASSWORD_RECOVERY);

        if ($token !== null) {
            return $this->json([
                'status' => 'token_already_sent',
            ]);
        }

        $emailService->sendPasswordRecoveryConfirmation($user);

        return $this->json([
            'status' => 'success',
        ]);
    }

    /**
     * @Route("/api/passwordRecovery", methods={"POST"});
     *
     * @param PasswordRecoveryRequest     $request
     * @param ConfirmationTokenRepository $tokenRepository
     * @param ApiTokenRepository          $apiTokenRepository
     *
     * @return JsonResponse
     */
    public function postRecoverPassword(PasswordRecoveryRequest $request, ConfirmationTokenRepository $tokenRepository, ApiTokenRepository $apiTokenRepository)
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
