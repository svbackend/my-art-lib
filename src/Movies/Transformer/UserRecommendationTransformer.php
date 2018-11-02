<?php

namespace App\Movies\Transformer;

use App\Transformer\Transformer;

class UserRecommendationTransformer implements Transformer
{
    private $hiddenFields = [];
    /**
     * [int movie id => int how many times this movie were recommended].
     *
     * @var array
     */
    private $idToTimesRecommendedMap = [];

    public static function list(array $ids): self
    {
        $transformer = new self();
        $transformer->hiddenFields = [
            'budget',
            'genres',
            'imdbId',
            'overview',
            'runtime',
            'tmdb.id',
            'tmdb.voteAverage',
            'tmdb.voteCount',
        ];

        foreach ($ids as $data) {
            $transformer->idToTimesRecommendedMap[reset($data)] = $data['rate'];
        }

        return $transformer;
    }

    public function process(array $data): array
    {
        if (!isset($data['id'])) {
            return $data;
        }

        $data['isWatched'] = isset($data['userWatchedMovie']) && $data['userWatchedMovie'] ? true : false;

        if (isset($data['tmdb.id'])) {
            $data['tmdb']['id'] = $data['tmdb.id'];
            $data['tmdb']['voteAverage'] = $data['tmdb.voteAverage'];
            $data['tmdb']['voteCount'] = $data['tmdb.voteCount'];

            $data['rate'] = $this->idToTimesRecommendedMap[$data['id']] ?? 0;
        }

        foreach ($this->hiddenFields as $hiddenField) {
            if (isset($data[$hiddenField])) {
                unset($data[$hiddenField]);
            }
        }

        return $data;
    }
}
