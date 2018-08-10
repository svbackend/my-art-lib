<?php

declare(strict_types=1);

namespace App\Movies\Pagination;

use App\Movies\Entity\Movie;
use App\Pagination\PaginatedCollectionInterface;

class MovieCollection implements PaginatedCollectionInterface
{
    private $offset = 0;
    private $limit = 20;
    private $totalCount;
    private $movies;

    /**
     * @param array|Movie[]|\Iterator $movies
     * @param $totalCount
     * @param int $offset
     */
    public function __construct($movies, $totalCount, int $offset = 0)
    {
        $this->totalCount = abs($totalCount);
        $this->offset = abs($offset);
        $this->movies = $movies;
    }

    public function getItems()
    {
        return $this->movies;
    }

    public function getTotal(): int
    {
        return (int) $this->totalCount;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
