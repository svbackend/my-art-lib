<?php

namespace App\Filters;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\ParameterBag;

interface Filter
{
    public function handle(ParameterBag $params, QueryBuilder $query): QueryBuilder;
}