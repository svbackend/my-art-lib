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
        $releasesPosition = mb_strpos($html, 'id="releases"');
        if ($releasesPosition === false) {
            // no releases yet on imdb
            return [];
        }
        $html = mb_substr($html, $releasesPosition);

        $akasPosition = mb_strpos($html, 'id="akas"');
        if ($akasPosition === false) {
            $html = mb_substr($html, 0);
        } else {
            $html = mb_substr($html, 0, $akasPosition);
        }

        $crawler = new Crawler($html, $this->getEndpoint($movie->getImdbId()));

        $tds = $crawler->filterXPath('//td')->getIterator();

        $result = [];
        $country = '';
        foreach ($tds as $td) {
            if ($td->getAttribute('class') === 'release-date-item__date') {
                $result[$country] = $this->imdbMapper->dateToObject($td->textContent);
                continue;
            }

            if ($td->hasChildNodes() && $td->getElementsByTagName('a')->item(0) !== null) {
                $country = $td->getElementsByTagName('a')->item(0)->textContent;
                $country = trim($country);
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
