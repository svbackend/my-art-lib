<?php

declare(strict_types=1);

namespace App\Pagination;

interface PaginatedCollectionInterface
{
    public function getItems();

    public function getTotal(): int;

    public function getOffset(): int;

    public function getLimit(): int;
}
