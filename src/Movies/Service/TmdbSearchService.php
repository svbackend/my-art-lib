<?php
declare(strict_types=1);

namespace App\Movies\Service;

use App\Movies\Exception\TmdbMovieNotFoundException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class TmdbSearchService
{
    private $apiKey;
    private $client;
    private $logger;
    private const ApiUrl = 'https://api.themoviedb.org/3';

    public function __construct(LoggerInterface $logger)
    {
        $this->apiKey = \getenv('MOVIE_DB_API_KEY');
        $this->client = new Client();
        $this->logger = $logger;
    }

    public function findMoviesByQuery(string $query, string $locale = 'en', $data = []): array
    {
        $data = array_merge([
            'api_key' => $this->apiKey,
            'language' => $locale,
            'query' => $query
        ], $data);

        $movies = $this->request('/search/movie', 'GET', [
            'query' => $data,
        ]);

        return $movies;
    }

    /**
     * @param int $tmdb_id
     * @param string $locale
     * @return array
     * @throws TmdbMovieNotFoundException
     */
    public function findMovieById(int $tmdb_id, string $locale = 'en'): array
    {
        $movie = $this->request("/movie/{$tmdb_id}", 'GET', [
            'query' => [
                'api_key' => $this->apiKey,
                'language' => $locale,
            ],
        ]);

        if (isset($movie['status_code']) && $movie['status_code'] == 34) {
            throw new TmdbMovieNotFoundException();
        }

        return $movie;
    }

    private function request(string $url, string $method = 'GET', array $params = []): array
    {
        $url = self::ApiUrl . $url;

        try {
            $response = $this->client->request($method, $url, $params);
            $response = json_decode($response->getBody()->getContents(), true);
            getenv('APP_ENV') === 'dev' && $this->logger->debug('Guzzle request:', [
                'url' => $url,
                'method' => $method,
                'params' => $params,
                'response' => $response,
            ]);
        } catch (GuzzleException $exception) {
            $this->logger->error('Guzzle request failed.', [
                'url' => $url,
                'method' => $method,
                'params' => $params,
                'exceptionMessage' => $exception->getMessage(),
                'exceptionCode' => $exception->getCode(),
            ]);

            $response = [];

            if ($exception->getCode() == 404) {
                $response = [
                    'status_code' => 34
                ];
            }
        }

        return $response;
    }
}