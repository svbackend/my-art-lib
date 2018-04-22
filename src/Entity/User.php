<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
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
    const ROLE_ADMIN = 'ROLE_ADMIN';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    #private $tokens;

    /**
     * @var $profile UserProfile
     * @ORM\OneToOne(targetEntity="App\Entity\UserProfile", cascade={"persist", "remove"})
     */
    private $profile;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    public $email;

    /**
     * @ORM\Column(type="integer", length=1, options={"default" : 0})
     */
    private $isEmailConfirmed = 0;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    public $username;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $roles;

    /**
     * @Exclude
     */
    private $plainPassword;

    /**
     * @Exclude
     * @ORM\Column(type="string", length=64)
     */
    private $password;

    public function __construct()
    {
        $this->addRole(self::ROLE_USER);
        $this->profile = new UserProfile($this);
    }

    public function getProfile(): UserProfile
    {
        return $this->profile;
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

    public function setPlainPassword(string $plainPassword): self
    {
        if (!empty($plainPassword)) {
            $this->plainPassword = $plainPassword;
            // Change some mapped values so preUpdate will get called.
            $this->password = ''; // just blank it out
        }

        return $this;
    }

    public function getPlainPassword(): string
    {
        return (string)$this->plainPassword;
    }

    public function setPassword($password, UserPasswordEncoderInterface $passwordEncoder): self
    {
        $this->password = $passwordEncoder->encodePassword($this, $password);

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

    public function confirmEmail()
    {
        $this->isEmailConfirmed = 1;

        return $this;
    }

    public function changeEmail($email)
    {
        $this->email = $email;
        $this->isEmailConfirmed = 0;

        return $this;
    }
}