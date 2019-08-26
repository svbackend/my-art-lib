<?php

namespace App\Movies\Parser;

use Symfony\Component\DomCrawler\Crawler;

class Megogo
{
    private const ENDPOINT = 'https://megogo.net/ua/films/main?q={title}&view_type=list&ajax=true&widget=widget_7';

    public function getUrlByTitle(string $originalTitle): string
    {
        $endpoint = strtr(self::ENDPOINT, [
            '{title}' => urlencode($originalTitle)
        ]);

        $html = $this->getHtml($endpoint);

        if (!$html) {
            throw new \ErrorException('Html cant be loaded');
        }

        $crawler = new Crawler($html);
        $searchTitle = trim($crawler->filter('.search-results-title')->first()->text());

        if (!$searchTitle) {
            throw new \ErrorException('Cant find search title');
        }

        $firstWord = current(explode(' ', $searchTitle));
        if (mb_strtolower($firstWord) !== 'найдено') {
            throw new \ErrorException('This movie not found on megogo');
        }

        $url = $crawler->filter('a.invisible-overlay')->first()->attr('href');

        if (!$url) {
            throw new \ErrorException('Url of movie not found');
        }

        return 'https://megogo.net' . $url;
    }

    private function getHtml(string $endpoint): string
    {
        $c = curl_init($endpoint);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($c);
        curl_close($c);

        if (!$json) {
            //todo write to log curl_error()
            return '';
        }

        $response = json_decode($json, true);

        return $response['data']['widgets']['widget_7']['html'];
    }
}