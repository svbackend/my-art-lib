<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Movies\Exception\TmdbMovieNotFoundException;
use App\Movies\Exception\TmdbRequestLimitException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

// todo use append_to_response + cache to decrease amount of requests to external api
class TmdbSearchService
{
    private $apiKey;
    private $client;
    private $logger;
    private const ApiUrl = 'https://api.themoviedb.org/3';

    public function __construct(LoggerInterface $logger, ClientInterface $client)
    {
        $this->apiKey = \getenv('MOVIE_DB_API_KEY'); // is it ok to use \getenv() here?
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * @param string $query
     * @param string $locale
     * @param array  $data
     *
     * @throws TmdbMovieNotFoundException
     * @throws TmdbRequestLimitException
     *
     * @return array
     */
    public function findMoviesByQuery(string $query, string $locale = 'en', $data = []): array
    {
        $data = array_merge([
            'api_key' => $this->apiKey,
            'language' => $locale,
            'query' => $query,
        ], $data);

        $movies = $this->request('/search/movie', 'GET', [
            'query' => $data,
        ]);

        return $movies;
    }

    /**
     * @param int    $tmdb_id
     * @param string $locale
     *
     * @throws TmdbMovieNotFoundException
     * @throws TmdbRequestLimitException
     *
     * @return array
     */
    public function findMovieById(int $tmdb_id, string $locale = 'en'): array
    {
        $movie = $this->request("/movie/{$tmdb_id}", 'GET', [
            'query' => [
                'api_key' => $this->apiKey,
                'language' => $locale,
            ],
        ]);

        return $movie;
    }

    public function findActorsByMovieId(int $personId, string $locale = 'en'): array
    {
        $actors = $this->request("/movie/{$personId}/credits", 'GET', [
            'query' => [
                'api_key' => $this->apiKey,
                'language' => $locale,
            ],
        ]);

        return $actors;
    }

    public function findActorById(int $personId, string $locale = 'en'): array
    {
        $actors = $this->request("/person/{$personId}", 'GET', [
            'query' => [
                'api_key' => $this->apiKey,
                'language' => $locale,
            ],
        ]);

        return $actors;
    }

    public function findActorTranslationsById(int $personId, string $locale = 'en'): array
    {
        $actors = $this->request("/person/{$personId}/translations", 'GET', [
            'query' => [
                'api_key' => $this->apiKey,
                'language' => $locale,
            ],
        ]);

        return $actors;
    }

    /**
     * @param int $tmdb_id
     *
     * @throws TmdbMovieNotFoundException
     * @throws TmdbRequestLimitException
     *
     * @return array
     */
    public function findMovieTranslationsById(int $tmdb_id): array
    {
        $movie = $this->request("/movie/{$tmdb_id}/translations", 'GET', [
            'query' => [
                'api_key' => $this->apiKey,
            ],
        ]);

        return $movie;
    }

    /**
     * @param int $tmdb_id
     * @param int $page
     *
     * @throws TmdbMovieNotFoundException
     * @throws TmdbRequestLimitException
     *
     * @return array
     */
    public function findSimilarMoviesById(int $tmdb_id, int $page = 1): array
    {
        $movie = $this->request("/movie/{$tmdb_id}/similar", 'GET', [
            'query' => [
                'api_key' => $this->apiKey,
                'page' => $page,
            ],
        ]);

        return $movie;
    }

    /**
     * @param string $url
     * @param string $method
     * @param array  $params
     *
     * @throws TmdbMovieNotFoundException
     * @throws TmdbRequestLimitException
     *
     * @return array
     */
    private function request(string $url, string $method = 'GET', array $params = []): array
    {
        $url = self::ApiUrl.$url;

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

            if ($exception->getCode() === 404) {
                throw new TmdbMovieNotFoundException();
            }

            if ($exception->getCode() === 429) {
                throw new TmdbRequestLimitException();
            }
        }

        return $response;
    }
}
