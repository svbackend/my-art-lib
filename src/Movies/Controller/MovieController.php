<?php

namespace App\Movies\Controller;

use App\Controller\ControllerInterface;
use App\Movies\Entity\Movie;
use FOS\RestBundle\Controller\FOSRestController;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;

/**
 * Class MovieController
 * @package App\Movies\Controller
 */
class MovieController extends FOSRestController implements ControllerInterface
{
    /**
     * Get all movies
     *
     * @Route("/api/movies", methods={"GET"})
     * @SWG\Response(
     *     description="REST action which returns all movies.",
     *     response=200,
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Movie::class, groups={"full"}))
     *     )
     * )
     *
     * @return array
     */
    public function getAll()
    {
        return $this->getDoctrine()->getRepository(Movie::class)->findAll();

    }
}