<?php

namespace App\Guests\Controller;

use App\Controller\BaseController;
use App\Guests\Entity\GuestSession;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class GuestSessionController extends BaseController
{
    /**
     * @Route("/api/guests", methods={"POST"});
     *
     * @throws \Exception
     *
     * @return JsonResponse
     */
    public function postGuests()
    {
        $newGuestSession = new GuestSession();

        $em = $this->getDoctrine()->getManager();
        $em->persist($newGuestSession);
        $em->flush();

        return $this->response($newGuestSession);
    }
}
