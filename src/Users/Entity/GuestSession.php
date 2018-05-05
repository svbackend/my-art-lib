<?php
declare(strict_types=1);

namespace App\Users\Entity;

use App\Users\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Users\Repository\GuestRepository")
 * @ORM\Table(name="guest_sessions")
 * @UniqueEntity(fields="token", message="This token already taken")
 */
class GuestSession
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=256, unique=true)
     */
    private $token;

    /**
     * @var \DateTimeInterface
     * @ORM\Column(type="date")
     */
    private $expires_at;

    /**
     * GuestSession constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->token = bin2hex(openssl_random_pseudo_bytes(32));
        $this->expires_at = new \DateTimeImmutable('+1 week');
    }

    public function getId()
    {
        return $this->id;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}