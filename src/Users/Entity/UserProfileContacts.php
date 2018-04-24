<?php
declare(strict_types=1);

namespace App\Users\Entity;

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
     * @ORM\ManyToOne(targetEntity="App\Users\Entity\UserProfile", inversedBy="contacts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $profile;

    /**
     * @ORM\Column(type="string", length=30)
     */
    private $provider;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $url;

    public function __construct(UserProfile $userProfile, string $provider, string $url)
    {
        $this->profile = $userProfile;
        $this->provider = $provider;
        $this->url = $url;
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
    public function getProfile(): UserProfile
    {
        return $this->profile;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}