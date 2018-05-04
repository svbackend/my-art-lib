<?php

namespace App\Users\Request;

use Symfony\Component\Validator\Constraints as Assert;
use App\Request\BaseRequest;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class AddWatchedMovieRequest extends BaseRequest
{
    public function rules()
    {
        $movieIdRequired = function ($object, ExecutionContextInterface $context, $payload) {
            $data = $context->getRoot()['movie'];
            $tmdb_id = $data['tmdbId'] ?? null;
            $id = $data['id'] ?? null;

            if (empty($tmdb_id) && empty($id)) {
                $context->buildViolation('Movie Id or TMDB Id should be provided')->addViolation();
            }
        };

        return new Assert\Collection([
            'movie' => new Assert\Collection([
                'id' => new Assert\Callback($movieIdRequired),
                'tmdbId' => new Assert\Callback($movieIdRequired),
                'vote' => [new Assert\Range(['min' => 0.0, 'max' => 10.0])],
                'watchedAt' => [new Assert\Date()],
            ]),
        ]);
    }
}