<?php

namespace App\Guests\Controller;

use App\Controller\BaseController;
use App\Guests\Entity\GuestSession;
use App\Movies\DTO\WatchedMovieDTO;
use App\Movies\Entity\Movie;
use App\Users\Entity\UserWatchedMovie;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\SearchService;
use App\Movies\Request\AddWatchedMovieRequest;
use App\Movies\Service\WatchedMovieService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class GuestSessionController extends BaseController
{
    /**
     * @Route("/api/guests", methods={"POST"});
     * @return JsonResponse
     * @throws \Exception
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