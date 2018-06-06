<?php

namespace App\Movies\Exception;

class TmdbRequestLimitException extends \Exception
{
    public function __construct($message = 'Request limit exceed', $code = 429, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
