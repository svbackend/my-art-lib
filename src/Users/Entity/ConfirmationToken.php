<?php

declare(strict_types=1);

namespace App\Users\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Users\Repository\ConfirmationTokenRepository")
 * @ORM\Table(name="users_confirmation_tokens")
 * @UniqueEntity(fields="token", message="This token already taken")
 */
class ConfirmationToken
{
    const TYPE_CONFIRM_EMAIL = 'confirm_email';
    const TYPE_PASSWORD_RECOVERY = 'password_recovery';

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
     * @ORM\Column(type="string", length=32, unique=true)
     */
    private $token;

    /**
     * @ORM\Column(type="string", length=256)
     */
    private $type;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var \DateTimeImmutable|\DateTimeInterface
     */
    private $expires_at;

    /**
     * ConfirmationToken constructor.
     *
     * @param \App\Users\Entity\User $user
     * @param $type
     * @param \DateTimeInterface|null $expires_at
     *
     * @throws \Exception
     */
    public function __construct(User $user, $type, \DateTimeInterface $expires_at = null)
    {
        if (\in_array($type, $this->getValidTypes(), true) === false) {
            throw new \InvalidArgumentException(sprintf('$type should be valid type! Instead %s given', $type));
        }

        if ($expires_at !== null) {
            $now = new \DateTimeImmutable();
            if ($expires_at <= $now) {
                throw new \InvalidArgumentException(sprintf('You can not create already expired token'));
            }
        }

        $this->type = $type;
        $this->token = bin2hex(openssl_random_pseudo_bytes(16));
        $this->user = $user;
        $this->expires_at = $expires_at;
    }

    public function getValidTypes(): array
    {
        return [self::TYPE_CONFIRM_EMAIL, self::TYPE_PASSWORD_RECOVERY];
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
     * @throws \Exception
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if (!$this->expires_at) {
            return true;
        }

        $now = new \DateTimeImmutable();

        return $this->expires_at > $now;
    }
}
