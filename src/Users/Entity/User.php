<?php
declare(strict_types=1);

namespace App\Users\Entity;

use App\Users\Entity\UserProfile;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * @ORM\Entity(repositoryClass="App\Users\Repository\UserRepository")
 * @ORM\Table(name="users")
 * @UniqueEntity(fields="email", message="Email already taken")
 * @UniqueEntity(fields="username", message="Username already taken")
 * @ExclusionPolicy("all")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Expose
     */
    private $id;

    /**
     * @var $profile UserProfile
     * @ORM\OneToOne(targetEntity="App\Users\Entity\UserProfile", cascade={"persist", "remove"})
     * @Expose
     */
    private $profile;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="integer", length=1, options={"default": 0})
     */
    private $isEmailConfirmed = 0;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Expose
     */
    private $username;

    /**
     * @ORM\Embedded(class="App\Users\Entity\UserRoles", columnPrefix=false)
     */
    private $roles;

    /**
     * @var string
     */
    private $plainPassword;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $password;

    public function __construct(string $email, string $username, string $password)
    {
        $this->roles = new UserRoles();
        $this->profile = new UserProfile($this);

        $this->email = $email;
        $this->username = $username;
        $this->setPlainPassword($password);
    }

    public function getProfile(): UserProfile
    {
        return $this->profile;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
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
        $this->plainPassword = $password;
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

    public function confirmEmail(): self
    {
        $this->isEmailConfirmed = 1;

        return $this;
    }

    public function changeEmail($email): self
    {
        $this->email = $email;
        $this->isEmailConfirmed = 0;

        return $this;
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        return $this->getRolesObject()->getRoles();
    }

    public function getRolesObject(): UserRoles
    {
        return $this->roles;
    }
}