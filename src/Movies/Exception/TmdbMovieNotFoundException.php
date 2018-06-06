<?php

namespace App\Movies\Exception;

class TmdbMovieNotFoundException extends \Exception
{
    public function __construct($message = 'Movie not found in TMDB', $code = 404, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
