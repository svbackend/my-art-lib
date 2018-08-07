<?php

declare(strict_types=1);

namespace App\Actors\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Embeddable
 */
class ActorTMDB
{
    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="integer", nullable=false, unique=true)
     */
    private $id;

    public function __construct(int $tmdbId)
    {
        $this->id = $tmdbId;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
