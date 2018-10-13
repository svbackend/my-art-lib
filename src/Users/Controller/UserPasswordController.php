<?php

namespace App\Users\Controller;

use App\Controller\BaseController;
use App\Users\Entity\User;
use App\Users\Request\ChangePasswordRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserPasswordController extends BaseController
{
    /**
     * @Route("/api/users/{id}/password", methods={"POST"}, requirements={"id"="\d+"})
     * @param ChangePasswordRequest $request
     * @param User $user
     * @param UserPasswordEncoderInterface $passwordEncoder
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
}
