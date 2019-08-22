<?php

namespace App\Movies\Controller;

use App\Controller\BaseController;
use App\Countries\Entity\Country;
use App\Filters\FilterBuilder;
use App\Filters\Movie as Filter;
use App\Movies\DTO\MovieTranslationDTO;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieTranslations;
use App\Movies\EventListener\SimilarMoviesProcessor;
use App\Movies\Repository\MovieReleaseDateRepository;
use App\Movies\Repository\MovieRepository;
use App\Movies\Request\CreateMovieRequest;
use App\Movies\Request\SearchRequest;
use App\Movies\Request\UpdateMovieRequest;
use App\Movies\Request\UpdatePosterRequest;
use App\Movies\Service\MovieManageService;
use App\Movies\Service\SearchService;
use App\Movies\Transformer\MovieTransformer;
use App\Movies\Utils\Poster;
use App\Pagination\CustomPaginatedCollection;
use App\Pagination\PaginatedCollection;
use App\Users\Entity\UserRoles;
use Doctrine\ORM\AbstractQuery;
use Enqueue\Client\ProducerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmptyMovieTranslationController extends BaseController
{
    /**
     * Get all movies without translation to specific locale
     *
     * @Route("/api/empty/movies/{locale}", methods={"GET"})
     *
     * @param Request         $request
     * @param MovieRepository $movieRepository
     * @param string  $locale
     *
     * @throws
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getAll(Request $request, MovieRepository $movieRepository, string $locale)
    {
        $moviesQuery = $movieRepository->findAllWithEmptyTranslation($locale);

        $offset = (int) $request->get('offset', 0);
        $limit = $request->get('limit', null);

        $moviesQuery->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);
        $collection = new PaginatedCollection($moviesQuery, $offset, $limit);

        return $this->json($collection);
    }
}
