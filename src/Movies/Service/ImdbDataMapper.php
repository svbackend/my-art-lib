<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Countries\Entity\Country;
use App\Countries\Repository\CountryRepository;

class ImdbDataMapper
{
    private $countryMap = [
        'Poland' => 'POL',
        'Ukraine' => 'UKR',
        'Russia' => 'RUS',
        'Belarus' => 'BLR',
    ];

    private $countryRepository;

    public function __construct(CountryRepository $countryRepository)
    {
        $this->countryRepository = $countryRepository;
    }

    /***
     * @throws
     */
    public function dateToObject(string $date): \DateTimeInterface
    {
        return new \DateTimeImmutable($date);
    }

    public function countryToCode(string $country): string
    {
        return $this->countryMap[$country] ?? $country;
    }
}
