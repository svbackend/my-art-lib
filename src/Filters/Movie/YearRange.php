<?php

namespace App\Filters\Movie;

use App\Filters\Filter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\ParameterBag;

class YearRange implements Filter
{
    public function handle(ParameterBag $params, QueryBuilder $qb): QueryBuilder
    {
        $yearFrom = (int)$params->get('yf');
        $yearTo = (int)$params->get('yt');
        $year = (int)$params->get('y');

        if ($yearFrom > $yearTo) {
            return $qb;
        }

        if ($yearFrom && $yearFrom === $yearTo) {
            $year = $yearFrom;
        }

        if ($this->isValid($year)) {
            return $qb
                ->andWhere("DATE_PART('year', m.releaseDate) = :filter_year")
                ->setParameter('filter_year', $year);
        }

        if ($this->isValid($yearFrom)) {
            $qb
                ->andWhere("DATE_PART('year', m.releaseDate) >= :filter_year_from")
                ->setParameter('filter_year_from', $yearFrom);
        }

        if ($this->isValid($yearTo)) {
            $qb
                ->andWhere("DATE_PART('year', m.releaseDate) <= :filter_year_to")
                ->setParameter('filter_year_to', $yearTo);
        }

        return $qb;
    }



    private function isValid(int $year): bool
    {
        return !($year < 1878 || $year > 2050);
    }
}