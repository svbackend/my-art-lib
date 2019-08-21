<?php

namespace App\Movies\Parser;

class Kinobaza
{
    private const URL = 'https://kinobaza.com.ua';
    private const SEARCH_ENDPOINT = self::URL . '/titles?q={title}&ys={year}&ye={year}&per_page=1&display=list_old';

    public function find(string $title, int $year): array
    {
        $endpoint = strtr(self::SEARCH_ENDPOINT, [
            '{title}' => urlencode($title),
            '{year}' => $year,
        ]);

        $html = $this->getHtml($endpoint);

        if (empty($html)) {
            return [];
        }

        $html = substr($html, strpos($html, '<body>'));

        $title = $this->getTitle($html);
        $overview = $this->getOverview($html);

        if (!$title) {
            return [];
        }

        return [
            'title' => $title,
            'overview' => $overview,
        ];
    }

    private function getHtml(string $endpoint): string
    {
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

    private function getTitle(string $html): string
    {
        $beginStr = 'itemprop="name">';
        $startPos = strpos($html, $beginStr);

        if ($startPos === false) {
            return '';
        }

        $startPos += strlen($beginStr);
        $endPos = strpos($html, '</span>', $startPos);

        $length = $endPos - $startPos;
        return substr($html, $startPos, $length);
    }

    private function getOverview(string $html): string
    {
        $beginStr = '<div class="col-12 mt-2">';
        $startPos = strpos($html, $beginStr);

        if ($startPos === false) {
            return '';
        }

        $startPos += strlen($beginStr);
        $endPos = strpos($html, '</div>', $startPos);

        $length = $endPos - $startPos;
        return substr($html, $startPos, $length);
    }
}