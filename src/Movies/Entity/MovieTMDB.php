<?php
declare(strict_types=1);

namespace App\Movies\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
class MovieTMDB
{
    /**
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="decimal", nullable=true)
     */
    private $voteAverage;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $voteCount;

    public function __construct(int $tmdbId)
    {
        $this->id = $tmdbId;
    }

    /**
     * @param float $voteAverage
     * @return MovieTMDB
     */
    public function setVoteAverage(float $voteAverage): self
    {
        $this->voteAverage = $voteAverage;
        return $this;
    }

    /**
     * @param int $voteCount
     * @return MovieTMDB
     */
    public function setVoteCount(int $voteCount): self
    {
        $this->voteCount = $voteCount;
        return $this;
    }


}