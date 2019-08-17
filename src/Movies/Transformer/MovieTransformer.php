<?php

namespace App\Movies\Transformer;

use App\Transformer\Transformer;

class MovieTransformer implements Transformer
{
    private $hiddenFields = [];

    public static function list(): self
    {
        $transformer = new self();
        $transformer->hiddenFields = [
            'budget',
            //'genres',
            'imdbId',
            'overview',
            'runtime',
            'tmdb.id',
            'tmdb.voteAverage',
            'tmdb.voteCount',
        ];

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
        }

        foreach ($this->hiddenFields as $hiddenField) {
            if (isset($data[$hiddenField])) {
                unset($data[$hiddenField]);
            }
        }

        return $data;
    }
}
