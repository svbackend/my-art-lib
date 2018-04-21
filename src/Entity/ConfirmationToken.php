<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ConfirmationTokenRepository")
 * @ORM\Table(name="users_confirmation_tokens")
 * @UniqueEntity(fields="token", message="This token already taken")
 */
class ConfirmationToken
{
    const TYPE_CONFIRM_EMAIl = 'confirm_email';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var $user User
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=32, unique=true)
     */
    private $token;

    /**
     * @ORM\Column(type="string", length=256)
     */
    private $type;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $expires_at;

    public static $validTypes = [self::TYPE_CONFIRM_EMAIl];

    public function __construct(User $user, $type)
    {
        if (in_array($type, self::$validTypes) === false) {
            throw new \InvalidArgumentException(sprintf('$type should be valid type! Instead %s given', $type));
        }

        $this->type = $type;
        $this->token = bin2hex(openssl_random_pseudo_bytes(16));
        $this->user = $user;
    }

    /**
     * @param \DateTimeInterface $expires_at
     * @return ConfirmationToken
     */
    public function setExpiresAt(\DateTimeInterface $expires_at)
    {
        $this->expires_at = $expires_at;
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
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getExpiresAt()
    {
        return $this->expires_at;
    }
}