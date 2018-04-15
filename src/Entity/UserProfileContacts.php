<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

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

    public function __construct(UserProfile $userProfile)
    {
        $this->profile = $userProfile;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return UserProfile
     */
    public function getProfile()
    {
        return $this->profile;
    }
}