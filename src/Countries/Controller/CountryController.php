<?php

namespace App\Countries\Controller;

use App\Controller\BaseController;
use App\Countries\Repository\CountryRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CountryController extends BaseController
{
    /**
     * @Route("/api/countries", methods={"GET"})
     *
     * @param Request           $request
     * @param CountryRepository $repository
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getAll(Request $request, CountryRepository $repository)
    {
        $name = $request->get('name', null);
        if ($name === null) {
            $countries = $repository->findAll();
        } else {
            $countries = $repository->findAllByName($name);
        }

        return $this->json($countries, 200, [], [
            'context' => ['groups' => ['view', 'list']],
        ]);
    }
}
