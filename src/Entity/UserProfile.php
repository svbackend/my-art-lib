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
     * @var $user User
     * @ORM\OneToOne(targetEntity="App\Entity\User", inversedBy="profile")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @var $contacts UserProfileContacts[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Entity\UserProfileContacts", mappedBy="contacts", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $contacts;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $first_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $last_name;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $birth_date;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $about;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $public_email;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->contacts = new ArrayCollection();
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
    public function getBirthDate()
    {
        return $this->birth_date;
    }

    /**
     * @param \DateTime $birth_date
     * @return UserProfile
     */
    public function setBirthDate(\DateTime $birth_date)
    {
        $this->birth_date = $birth_date;
        return $this;
    }

    /**
     * @return UserProfileContacts[]|ArrayCollection
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * @param $name
     * @param $url
     * @return $this
     */
    public function addContacts($name, $url)
    {
        $contact = new UserProfileContacts($this);
        $contact->provider = $name;
        $contact->url = $url;

        $this->contacts->add($contact);

        return $this;
    }
}