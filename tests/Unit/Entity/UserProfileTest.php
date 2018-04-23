<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Users\Entity\User;
use App\Users\Entity\UserProfile;
use App\Users\Entity\UserProfileContacts;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserProfileTest extends KernelTestCase
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
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->user = new User();
        $this->profile = $this->user->getProfile();
    }

    protected function tearDown()
    {
        $this->user = null;
        $this->profile = null;
        unset($this->user, $this->profile);
    }

    public function testIdIsEmpty()
    {
        $this->assertNull($this->profile->getId());
    }

    public function testGetUser()
    {
        $user = $this->profile->getUser();
        $this->assertEquals($this->user, $user);
    }

    public function testGetBirthDate()
    {
        $this->assertNull($this->profile->getBirthDate());
    }

    public function testSetBirthDate()
    {
        $birthDate = (new \DateTime())->modify('-20 years');
        $this->profile->setBirthDate($birthDate);
        $this->assertEquals($birthDate, $this->profile->getBirthDate());
    }

    public function testGetContacts()
    {
        $contacts = $this->profile->getContacts();
        $this->assertEquals(0, $contacts->count());
    }

    public function testAddContacts()
    {
        $contacts_name = 'test1';
        $contacts_url = 'test2';

        $result = $this->profile->addContacts($contacts_name, $contacts_url);
        $contacts = $this->profile->getContacts();

        $this->assertEquals($this->profile, $result);
        $this->assertEquals(1, $contacts->count());
        $this->assertTrue($contacts->get(0) instanceof \App\Users\Entity\UserProfileContacts);
    }
}