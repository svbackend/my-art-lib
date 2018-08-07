<?php

declare(strict_types=1);

namespace App\Actors\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="actors_contacts")
 */
class ActorContacts
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Actors\Entity\Actor", inversedBy="contacts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $actor;

    /**
     * @ORM\Column(type="string", length=30)
     */
    private $provider;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $url;

    public function __construct(Actor $actor, string $provider, string $url)
    {
        $this->actor = $actor;
        $this->provider = $provider;
        $this->url = $url;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getActor(): Actor
    {
        return $this->actor;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
