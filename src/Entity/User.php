<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Expose;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="users")
 * @UniqueEntity(fields="email", message="Email already taken")
 * @UniqueEntity(fields="username", message="Username already taken")
 */
class User implements UserInterface, \Serializable
{
    const ROLE_USER = 'ROLE_USER';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    public $email;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank()
     */
    public $username;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $roles;

    /**
     * @Exclude
     * @Assert\NotBlank()
     * @Assert\Length(max=4096)
     */
    public $plainPassword;

    /**
     * @Exclude
     * @ORM\Column(type="string", length=64)
     */
    private $password;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     */
    private $apiKey;

    public function __construct()
    {
        $this->addRole(self::ROLE_USER);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function addRole(string $role): self
    {
        if (array_search($role, $this->getRoles()) === false) {
            array_push($this->roles, $role);
        }

        return $this;
    }

    public function removeRole(string $role): self
    {
        if (false !== $key = array_search($role, $this->roles)) {
            if (isset($this->roles[$key])) unset($this->roles[$key]);
        }

        return $this;
    }

    public function getRoles(): array
    {
        if (!$this->roles) {
            return [self::ROLE_USER];
        }

        return json_decode($this->roles);
    }

    public function setPassword($password, UserPasswordEncoderInterface $passwordEncoder): self
    {
        $this->plainPassword = $password;
        $this->password = $passwordEncoder->encodePassword($this, $this->plainPassword);

        return $this;
    }

    public function isPasswordValid($password, UserPasswordEncoderInterface $passwordEncoder): bool
    {
        return $passwordEncoder->isPasswordValid($this, $password);
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function eraseCredentials(): self
    {
        $this->plainPassword = null;

        return $this;
    }

    // todo remove apiKey from 2 methods below
    public function serialize(): string
    {
        return serialize([
            $this->id,
            $this->email,
            $this->username,
            $this->apiKey,
            $this->roles,
        ]);
    }

    public function unserialize($serialized): self
    {
        list (
            $this->id,
            $this->email,
            $this->username,
            $this->apiKey,
            $this->roles,
            ) = unserialize($serialized);

        return $this;
    }

    /**
     * @return string:null
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }


    public function generateApiKey(): self
    {
        $this->apiKey = bin2hex(openssl_random_pseudo_bytes(32));

        return $this;
    }
}