<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Countries\Repository\ImdbCountryRepository;

class ImdbDataMapper
{
    private $countryMap = [];

    private $countryRepository;

    private $isMapped = false;

    public function __construct(ImdbCountryRepository $countryRepository)
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
        if ($this->isMapped === false) {
            $this->isMapped = true;
            $countries = $this->countryRepository->findAll();
            foreach ($countries as $imdbCountry) {
                $this->countryMap[$imdbCountry->getName()] = $imdbCountry->getCountry()->getCode();
            }
        }

        return $this->countryMap[$country] ?? $country;
    }
}
