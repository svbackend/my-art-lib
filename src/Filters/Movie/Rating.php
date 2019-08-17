<?php

namespace App\Filters\Movie;

use App\Filters\Filter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * /api/movies?rf=5&rt=6 => Will display all movies with average tmdb rating.
 */
class Rating implements Filter
{
    public function handle(ParameterBag $params, QueryBuilder $qb): QueryBuilder
    {
        $rating = (int) $params->get('r');
        $ratingFrom = (int) $params->get('rf');
        $ratingTo = (int) $params->get('rt');

        if ($ratingTo && $ratingFrom > $ratingTo) {
            return $qb;
        }

        if ($ratingFrom && $ratingFrom === $ratingTo) {
            $rating = $ratingFrom;
        }

        if ($this->isValid($rating)) {
            return $qb
                ->andWhere('m.tmdb.voteAverage = :filter_rating')
                ->setParameter('filter_rating', $rating);
        }

        if ($this->isValid($ratingFrom)) {
            $qb
                ->andWhere('m.tmdb.voteAverage >= :filter_rating_from')
                ->setParameter('filter_rating_from', $ratingFrom);
        }

        if ($this->isValid($ratingTo)) {
            $qb
                ->andWhere('m.tmdb.voteAverage <= :filter_rating_to')
                ->setParameter('filter_rating_to', $ratingTo);
        }

        return $qb;
    }

    private function isValid(int $rating): bool
    {
        return !($rating < 1 || $rating > 10);
    }
}
