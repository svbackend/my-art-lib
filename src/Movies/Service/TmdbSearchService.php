<?php
declare(strict_types=1);

namespace App\Movies\Service;

use App\Movies\Repository\MovieRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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

    public function findMoviesByQuery(string $query, string $locale = 'en-US'): array
    {
        $movies = $this->request('/search/movie', 'GET', [
            'query' => [
                'api_key' => $this->apiKey,
                'locale' => $locale,
                'query' => $query
            ],
        ]);

        return $movies;
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
        }

        return $response;
    }
}