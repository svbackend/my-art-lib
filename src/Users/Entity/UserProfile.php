<?php

declare(strict_types=1);

namespace App\Users\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\Table(name="users_profiles")
 */
class UserProfile
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var UserProfileContacts[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Users\Entity\UserProfileContacts", mappedBy="profile", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @Groups({"view"})
     */
    private $contacts;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"list", "view"})
     */
    private $first_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"list", "view"})
     */
    private $last_name;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Groups({"view"})
     */
    private $birth_date;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"view"})
     */
    private $about;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"view"})
     */
    private $public_email;

    public function __construct()
    {
        $this->contacts = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * @param mixed $first_name
     *
     * @return UserProfile
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * @param mixed $last_name
     *
     * @return UserProfile
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAbout()
    {
        return $this->about;
    }

    /**
     * @param mixed $about
     *
     * @return UserProfile
     */
    public function setAbout($about)
    {
        $this->about = $about;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublicEmail()
    {
        return $this->public_email;
    }

    /**
     * @param mixed $public_email
     *
     * @return UserProfile
     */
    public function setPublicEmail($public_email)
    {
        $this->public_email = $public_email;

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
     * @return mixed
     */
    public function getBirthDate()
    {
        return $this->birth_date;
    }

    /**
     * @param \DateTimeInterface $birth_date
     *
     * @return UserProfile
     */
    public function setBirthDate(\DateTimeInterface $birth_date)
    {
        $this->birth_date = $birth_date;

        return $this;
    }

    /**
     * @return UserProfileContacts[]|ArrayCollection
     */
    public function getContacts()
    {
        return $this->contacts->toArray();
    }

    /**
     * @param $name
     * @param $url
     *
     * @return $this
     */
    public function addContacts($name, $url)
    {
        $contact = new UserProfileContacts($this, $name, $url);
        $this->contacts->add($contact);

        return $this;
    }
}
