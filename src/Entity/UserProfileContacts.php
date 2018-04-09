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
 * @ORM\Entity
 * @ORM\Table(name="users_profiles_contacts")
 */
class UserProfileContacts
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserProfile", inversedBy="contacts")
     * @ORM\JoinColumn(nullable=true)
     */
    private $profile;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $provider;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $url;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $profile
     * @return UserProfileContacts
     */
    public function setProfile(UserProfile $profile): self
    {
        $this->profile = $profile;
        return $this;
    }
}