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

    public function __construct(int $id, float $voteAverage = null, int $voteCount = null)
    {
        $this->id = $id;
        $this->voteAverage = $voteAverage;
        $this->voteCount = $voteCount;
    }
}