<?php

namespace App\Movies\Request;

use App\Movies\Entity\MovieCard;
use App\Request\BaseRequest;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class NewMovieCardRequest extends BaseRequest
{
    public function rules()
    {
        $cardTypes = [
            MovieCard::TYPE_WATCH,
            MovieCard::TYPE_WATCH_FREE,
            MovieCard::TYPE_DOWNLOAD,
            MovieCard::TYPE_REVIEW,
            MovieCard::TYPE_TRAILER
        ];

        return new Assert\Collection([
            'card' => new Assert\Collection([
                'title' => [new Assert\NotBlank(), new Assert\Length(['min' => 2, 'max' => 255])],
                'description' => new Assert\Length(['max' => 255]),
                'url' => [new Assert\NotBlank(), new Assert\Length(['max' => 255]), new Assert\Url()],
                'type' => [new Assert\NotBlank(), new Assert\Choice($cardTypes)],
            ]),
        ]);
    }
}
