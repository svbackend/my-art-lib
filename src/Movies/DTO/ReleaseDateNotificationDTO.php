<?php

declare(strict_types=1);

namespace App\Movies\DTO;

class ReleaseDateNotificationDTO
{
    public $movieId;
    public $movieTitle;
    public $releaseDate;
    public $countryName;

    public function __construct(int $movieId,  string $movieTitle, string $countryName)
    {
        $this->movieId = $movieId;
        $this->movieTitle = $movieTitle;
        $this->releaseDate = date('Y-m-d');
        $this->countryName = $countryName;
    }
}
