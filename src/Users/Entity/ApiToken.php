<?php

declare(strict_types=1);

namespace App\Users\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Users\Repository\ApiTokenRepository")
 * @ORM\Table(name="users_api_tokens")
 * @UniqueEntity(fields="token", message="This token already taken")
 */
class ApiToken
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="App\Users\Entity\User")
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=256, unique=true)
     */
    private $token;

    public function __construct(User $user)
    {
        $this->token = bin2hex(openssl_random_pseudo_bytes(128));
        $this->user = $user;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
