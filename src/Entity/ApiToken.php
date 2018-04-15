<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Expose;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ApiTokenRepository")
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
     * @var $profile UserProfile
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="apiTokens")
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=256, unique=true)
     */
    private $token;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->token = bin2hex(openssl_random_pseudo_bytes(128));
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function __toString()
    {
        return $this->token;
    }
}