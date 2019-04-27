<?php

declare(strict_types=1);

namespace App\Movies\Event;

use App\Movies\Entity\Movie;
use Symfony\Component\EventDispatcher\Event;

class MovieAddedFromTmdbEvent extends Event
{
    public const NAME = 'movie.addedFromTmdb';

    private $movie;

    public function __construct(Movie $movie)
    {
        $this->movie = $movie;
    }

    public function getMovie(): Movie
    {
        return $this->movie;
    }
}
