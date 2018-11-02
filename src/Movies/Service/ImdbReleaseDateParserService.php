<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Movies\Entity\Movie;
use Symfony\Component\DomCrawler\Crawler;

class ImdbReleaseDateParserService
{
    private const IMDB_RELEASE_DATES_URL = 'https://imdb.com/title/{id}/releaseinfo?ref_=tt_dt_dt';

    private $imdbMapper;

    public function __construct(ImdbDataMapper $mapper)
    {
        $this->imdbMapper = $mapper;
    }

    public function getReleaseDates(Movie $movie): array
    {
        if ($movie->getImdbId() === null) {
            return [];
        }

        $html = $this->loadImdbReleaseDatesPageHtml($movie->getImdbId());
        $crawler = new Crawler($html, self::IMDB_RELEASE_DATES_URL);

        $tds = $crawler->filterXPath('//*[@id="release_dates"]//td')->getIterator();

        $result = [];
        $country = '';
        foreach ($tds as $td) {
            if ($td->getAttribute('class') === 'release_date') {
                $result[$country] = $this->imdbMapper->dateToObject($td->textContent);
                continue;
            }

            if ($td->hasChildNodes() && $td->getElementsByTagName('a')->item(0) !== null) {
                $country = $td->getElementsByTagName('a')->item(0)->textContent;
                $country = $this->imdbMapper->countryToCode($country);
                continue;
            }
        }

        return $result;
    }

    private function loadImdbReleaseDatesPageHtml(string $imdbId): string
    {
        $endpoint = str_replace('{id}', $imdbId, self::IMDB_RELEASE_DATES_URL);

        $c = curl_init($endpoint);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

        $html = curl_exec($c);

        if (curl_error($c)) {
            //todo write to log
            return '';
        }

        curl_close($c);

        return $html;
    }
}
