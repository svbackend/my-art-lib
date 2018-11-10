<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Movies\Entity\Movie;
use Symfony\Component\DomCrawler\Crawler;

class ImdbReleaseDateParserService
{
    private const IMDB_RELEASE_DATES_URL = 'https://www.imdb.com/title/{id}/releaseinfo?ref_=tt_dt_dt';

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

        // Because filterXPath('//*[@id="release_dates"]//td') don't work correctly due errors in html from imdb page
        $html = substr($html, strpos($html, '<a id="releases" name="releases"></a>'));
        $html = substr($html, 0, strpos($html, '<a id="akas" name="akas"></a>'));

        $crawler = new Crawler($html, $this->getEndpoint($movie->getImdbId()));

        $tds = $crawler->filterXPath('//td')->getIterator();

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

    private function getEndpoint(string $imdbId): string
    {
        return str_replace('{id}', $imdbId, self::IMDB_RELEASE_DATES_URL);
    }

    private function loadImdbReleaseDatesPageHtml(string $imdbId): string
    {
        $endpoint = $this->getEndpoint($imdbId);

        $c = curl_init($endpoint);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

        $html = curl_exec($c);

        if ($html === false) {
            //todo write to log curl_error()
            return '';
        }

        curl_close($c);

        return $html;
    }
}
