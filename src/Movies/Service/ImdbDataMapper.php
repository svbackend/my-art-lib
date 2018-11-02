<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Countries\Repository\ImdbCountryRepository;

class ImdbDataMapper
{
    private $countryMap = [];

    private $countryRepository;

    public function __construct(ImdbCountryRepository $countryRepository)
    {
        $this->countryRepository = $countryRepository;

        $countries = $countryRepository->findAll();

        foreach ($countries as $imdbCountry) {
            $this->countryMap[$imdbCountry->getName()] = $imdbCountry->getCountry()->getCode();
        }
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
