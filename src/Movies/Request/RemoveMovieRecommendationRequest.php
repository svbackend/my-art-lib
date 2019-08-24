<?php

namespace App\Movies\Request;

use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RemoveMovieRecommendationRequest extends BaseRequest
{
    public function rules()
    {
        $movieIdRequired = function ($object, ExecutionContextInterface $context, $payload) {
            $data = $context->getRoot();
            $tmdb_id = $data['tmdb_id'] ?? null;
            $id = $data['movie_id'] ?? null;

            if (empty($tmdb_id) && empty($id)) {
                $context->buildViolation('Movie Id or TMDB Id should be provided')->addViolation();
            }
        };

        return new Assert\Collection([
            'movie_id' => new Assert\Callback($movieIdRequired),
            'tmdb_id' => new Assert\Callback($movieIdRequired),
        ]);
    }
}
