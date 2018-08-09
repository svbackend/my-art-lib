<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Users\Entity\User;
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
        $this->user = new User('tester@tester.com', 'tester', 'tester');
        $this->profile = $this->user->getProfile();
        $this->contacts = new UserProfileContacts($this->profile, 'Instagram', 'https://instagram.com/tester');
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
        $this->assertSame($this->profile, $this->contacts->getProfile());
    }

    public function testGetId()
    {
        $this->assertNull($this->contacts->getId());
    }

    public function testGetContactsData()
    {
        $this->assertSame('Instagram', $this->contacts->getProvider());
        $this->assertSame('https://instagram.com/tester', $this->contacts->getUrl());
    }
}
