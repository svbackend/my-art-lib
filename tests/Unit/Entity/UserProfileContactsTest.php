<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Users\Entity\User;
use App\Users\Entity\UserProfile;
use App\Users\Entity\UserProfileContacts;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserProfileContactsTest extends KernelTestCase
{
    /**
     * @var \App\Users\Entity\User
     */
    protected $user;

    /**
     * @var \App\Users\Entity\UserProfile
     */
    protected $profile;

    /**
     * @var UserProfileContacts
     */
    protected $contacts;

    protected function setUp()
    {
        $this->user = new \App\Users\Entity\User();
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