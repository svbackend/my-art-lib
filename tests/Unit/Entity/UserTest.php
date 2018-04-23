<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Users\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserTest extends KernelTestCase
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var UserPasswordEncoderInterface
     */
    protected $passwordEncoder;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();

        $this->user = new \App\Users\Entity\User();
        $this->passwordEncoder = $kernel->getContainer()->get('security.password_encoder');
    }
    
    public function testGetId()
    {
        $this->assertEmpty($this->user->getId());
    }

    public function testGetRoles()
    {
        $this->assertEquals([$this->user::ROLE_USER], $this->user->getRoles());
    }

    public function testAddRole()
    {
        $this->user->addRole('ROLE_MODERATOR');
        $this->assertEquals([$this->user::ROLE_USER, 'ROLE_MODERATOR'], $this->user->getRoles());
    }

    public function testRemoveRole()
    {
        $this->user->removeRole($this->user::ROLE_USER);
        $this->assertEquals([$this->user::ROLE_USER], $this->user->getRoles());

        $this->user->addRole('ROLE_MODERATOR');
        $this->user->removeRole($this->user::ROLE_USER);
        $this->assertEquals(['ROLE_MODERATOR'], $this->user->getRoles());

        $this->user->removeRole('ROLE_MODERATOR');
        $this->assertEquals([$this->user::ROLE_USER], $this->user->getRoles());
    }

    public function testIsPasswordValid()
    {
        $this->user->setPassword('123456', $this->passwordEncoder);
        $this->assertEquals(false, $this->user->isPasswordValid('wrongPassword', $this->passwordEncoder));
        $this->assertEquals(true, $this->user->isPasswordValid('123456', $this->passwordEncoder));
    }

    public function testGetSalt()
    {
        $this->assertEquals(null, $this->user->getSalt());
    }

    public function testGetEmptyUsername()
    {
        $this->assertEquals(null, $this->user->getUsername());
    }

    public function testGetFilledUsername()
    {
        $this->user->username = 'Tester';
        $this->assertEquals('Tester', $this->user->getUsername());
    }

    public function testEraseCredentials()
    {
        $this->user->setPassword('123456', $this->passwordEncoder);
        $this->user->eraseCredentials();
        $this->assertEquals(null, $this->user->getPlainPassword());
    }

    public function testSerialize()
    {
        $this->user->addRole('ROLE_MODERATOR');
        $this->user->username = 'tester';
        $this->user->email = 'tester@tester.com';
        $serializedUser = $this->user->serialize();

        $unserializedUser = (new User)->unserialize($serializedUser);
        $this->assertEquals('tester', $unserializedUser->getUsername());
        $this->assertEquals('tester@tester.com', $unserializedUser->email);
        $this->assertEquals($this->user->getRoles(), $unserializedUser->getRoles());
    }
}