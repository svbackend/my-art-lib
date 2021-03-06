<?php

namespace App\Movies\Request;

use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;

class CreateMovieRequest extends BaseRequest
{
    public function rules()
    {
        return new Assert\Collection([
            'movie' => new Assert\Collection([
                // Movie
                'originalTitle' => [new Assert\NotBlank(), new Assert\Length(['min' => 2, 'max' => 100])],
                'originalPosterUrl' => [new Assert\NotBlank(), new Assert\Length(['min' => 10, 'max' => 255])],
                'imdbId' => new Assert\Length(['min' => 5, 'max' => 20]),
                'runtime' => new Assert\Type(['type' => 'integer']),
                'budget' => new Assert\Type(['type' => 'integer']),
                'releaseDate' => new Assert\Date(),
                // MovieTranslations[]
                'translations' => $this->eachItemValidation([
                    'locale' => [new Assert\NotBlank(), new Assert\Locale()],
                    'title' => [new Assert\NotBlank(), new Assert\Length(['min' => 3, 'max' => 50])],
                    'posterUrl' => [new Assert\NotBlank(), new Assert\Length(['min' => 10, 'max' => 255])],
                    'overview' => [new Assert\NotBlank(), new Assert\Length(['min' => 50])],
                ]),
                'genres' => $this->eachItemValidation([
                    'id' => [new Assert\NotBlank(), new Assert\Type('integer')],
                ]),
                // MovieTMDB
                'tmdb' => new Assert\Collection([
                    'id' => [new Assert\NotBlank(), new Assert\Type(['type' => 'integer'])],
                    'voteAverage' => new Assert\Range(['min' => 0.0, 'max' => 10.0]),
                    'voteCount' => new Assert\Type(['type' => 'integer']),
                ]),
            ]),
        ]);
    }
}
