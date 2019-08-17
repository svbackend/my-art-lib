<?php

namespace App\Filters;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\ParameterBag;

final class FilterBuilder
{
    /** @var $filters Filter[] */
    private $filters = [];

    public function __construct(Filter ...$filters)
    {
        foreach ($filters as $filter) {
            $this->filters[\get_class($filter)] = $filter;
        }
    }

    public function process(ParameterBag $params, QueryBuilder $qb): QueryBuilder
    {
        foreach ($this->filters as $filter) {
            $filter->handle($params, $qb);
        }

        return $qb;
    }
}
