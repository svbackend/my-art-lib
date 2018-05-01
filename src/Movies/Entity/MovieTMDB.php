<?php
declare(strict_types=1);

namespace App\Movies\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Embeddable
 */
class MovieTMDB
{
    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="decimal", nullable=true)
     */
    private $voteAverage;

    /**
     * @Groups({"list", "view"})
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

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getVoteAverage()
    {
        return $this->voteAverage;
    }

    /**
     * @return mixed
     */
    public function getVoteCount()
    {
        return $this->voteCount;
    }
}