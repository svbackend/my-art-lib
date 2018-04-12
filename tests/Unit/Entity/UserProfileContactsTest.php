<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Entity\UserProfileContacts;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserProfileContactsTest extends KernelTestCase
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var UserProfile
     */
    protected $profile;

    /**
     * @var UserProfileContacts
     */
    protected $contacts;

    protected function setUp()
    {
        $this->user = new User();
        $this->profile = $this->user->getProfile();
        $this->contacts = new UserProfileContacts($this->profile);
    }

    protected function tearDown()
    {
        $this->user = null;
        $this->profile = null;
        $this->contacts = null;
        unset($this->user, $this->profile, $this->contacts);
    }

    public function testGetProfile()
    {
        $this->assertEquals($this->profile, $this->contacts->getProfile());
    }

    public function testGetId()
    {
        $this->assertNull($this->contacts->getId());
    }
}