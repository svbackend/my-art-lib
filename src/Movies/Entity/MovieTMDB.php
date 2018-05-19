<?php
declare(strict_types=1);

namespace App\Movies\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Embeddable
 */
class MovieTMDB
{
    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="integer", unique=true)
     */
    private $id;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="decimal", nullable=true)
     */
    private $voteAverage;

    /**
     * @Groups({"view"})
     * @ORM\Column(type="integer", nullable=true)
     */
    private $voteCount;

    public function __construct(int $tmdbId, ?float $voteAverage, ?int $voteCount)
    {
        $this->id = $tmdbId;
        $this->voteAverage = $voteAverage;
        $this->voteCount = $voteCount;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getVoteAverage(): ?float
    {
        return (float)$this->voteAverage;
    }

    public function getVoteCount(): ?int
    {
        return (int)$this->voteCount;
    }
}