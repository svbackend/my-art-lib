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
    public $roles;

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
            return $this->setRoles(
                array_merge($this->getRoles(), [$role])
            );
        }

        return $this;
    }

    public function removeRole(string $role): self
    {
        $roles = $this->getRoles();
        $foundedRoleKey = array_search($role, $roles);

        if ($foundedRoleKey !== false) {
            unset($roles[$foundedRoleKey]);
            return $this->setRoles($roles);
        }

        return $this;
    }

    private function setRoles(array $roles): self
    {
        if (!count($roles)) {
            $this->roles = null;
            return $this;
        }

        $this->roles = json_encode($roles);

        return $this;
    }

    public function getRoles(): array
    {
        if (!$this->roles) {
            return $this->getDefaultRoles();
        }

        $roles = (array)json_decode($this->roles);

        if (!count($roles)) {
            return $this->getDefaultRoles();
        }

        return array_values($roles);
    }

    private function getDefaultRoles()
    {
        return [self::ROLE_USER];
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
            $this->roles,
        ]);
    }

    public function unserialize($serialized): self
    {
        list (
            $this->id,
            $this->email,
            $this->username,
            $this->roles,
            ) = unserialize($serialized);

        return $this;
    }
}