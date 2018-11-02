<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Movies\Exception\TmdbMovieNotFoundException;
use App\Movies\Exception\TmdbRequestLimitException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

// todo refactor cache usage
class TmdbSearchService
{
    private $apiKey;
    private $client;
    private $logger;
    private $cache;
    private const ApiUrl = 'https://api.themoviedb.org/3';

    public function __construct(LoggerInterface $logger, ClientInterface $client, CacheInterface $cache)
    {
        $this->apiKey = \getenv('MOVIE_DB_API_KEY'); // is it ok to use \getenv() here?
        $this->client = $client;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    /**
     * @param string $query
     * @param string $locale
     * @param array  $data
     *
     * @throws TmdbMovieNotFoundException
     * @throws TmdbRequestLimitException
     * @throws \Psr\SimpleCache\InvalidArgumentException
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
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return array
     */
    public function findMovieById(int $tmdb_id, string $locale = 'en'): array
    {
        $movie = $this->request("/movie/{$tmdb_id}", 'GET', [
            'query' => [
                'api_key' => $this->apiKey,
                'language' => $locale,
                'append_to_response' => 'similar,translations,credits',
            ],
        ]);

        $similarUrl = "/movie/{$tmdb_id}/similar";
        $params = ['api_key' => $this->apiKey, 'page' => 1];
        $this->cache->set($this->getCacheKeyFromParams($similarUrl, 'GET', ['query' => $params]), json_encode($movie['similar']), 1800);

        $translationsUrl = "/movie/{$tmdb_id}/translations";
        $params = ['api_key' => $this->apiKey];
        $this->cache->set($this->getCacheKeyFromParams($translationsUrl, 'GET', ['query' => $params]), json_encode($movie['translations']), 1800);

        $creditsUrl = "/movie/{$tmdb_id}/credits";
        $params = ['api_key' => $this->apiKey, 'language' => $locale];
        $this->cache->set($this->getCacheKeyFromParams($creditsUrl, 'GET', ['query' => $params]), json_encode($movie['credits']), 1800);

        return $movie;
    }

    /**
     * @param int    $movieId
     * @param string $locale
     *
     * @throws TmdbMovieNotFoundException
     * @throws TmdbRequestLimitException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return array
     */
    public function findActorsByMovieId(int $movieId, string $locale = 'en'): array
    {
        $actors = $this->request("/movie/{$movieId}/credits", 'GET', [
            'query' => [
                'api_key' => $this->apiKey,
                'language' => $locale,
            ],
        ]);

        return $actors;
    }

    /**
     * @param int    $personId
     * @param string $locale
     *
     * @throws TmdbMovieNotFoundException
     * @throws TmdbRequestLimitException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return array
     */
    public function findActorById(int $personId, string $locale = 'en'): array
    {
        $actor = $this->request("/person/{$personId}", 'GET', [
            'query' => [
                'api_key' => $this->apiKey,
                'language' => $locale,
                'append_to_response' => 'translations',
            ],
        ]);

        $translationsUrl = "/person/{$personId}/translations";
        $params = ['api_key' => $this->apiKey, 'language' => $locale];
        $this->cache->set($this->getCacheKeyFromParams($translationsUrl, 'GET', ['query' => $params]), json_encode($actor['translations']), 1800);

        return $actor;
    }

    /**
     * @param int    $personId
     * @param string $locale
     *
     * @throws TmdbMovieNotFoundException
     * @throws TmdbRequestLimitException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return array
     */
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
     * @throws \Psr\SimpleCache\InvalidArgumentException
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
     * @throws \Psr\SimpleCache\InvalidArgumentException
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
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return array
     */
    private function request(string $url, string $method = 'GET', array $params = []): array
    {
        if (null !== $cachedResponse = $this->getResponseFromCache($url, $method, $params)) {
            return $cachedResponse;
        }

        $url = self::ApiUrl.$url;

        try {
            $response = $this->client->request($method, $url, $params);
            $responseJson = $response->getBody()->getContents();
            $response = json_decode($responseJson, true);
            $this->cache->set($this->getCacheKeyFromParams($url, $method, $params), $responseJson, 1800);
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

    private function arrayAsString(array $array): string
    {
        $string = '';

        foreach ($array as $key => $value) {
            if (\is_array($value) === true) {
                $value = $this->arrayAsString($value);
            }
            $string .= "$key-$value";
        }

        return $string;
    }

    private function getCacheKeyFromParams(string $url, string $method = 'GET', array $params = []): string
    {
        $key = md5($url.mb_strtolower($method).$this->arrayAsString($params));

        return $key;
    }

    /**
     * @param string $url
     * @param string $method
     * @param array  $params
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return array|null
     */
    private function getResponseFromCache(string $url, string $method = 'GET', array $params = []): ?array
    {
        if (null !== $response = $this->cache->get($this->getCacheKeyFromParams($url, $method, $params))) {
            return json_decode($response, true);
        }

        return $response;
    }
}
