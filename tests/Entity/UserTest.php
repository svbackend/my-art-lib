<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    /**
     * @var User
     */
    protected $user;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->user = new User();
    }
    
    public function testGetId()
    {
        $this->assertEmpty($this->user->getId());
    }

    public function testGetRoles()
    {
        $this->assertEquals([$this->user::ROLE_USER], $this->user->getRoles());
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
        $this->user->plainPassword = '123456';
        $this->user->eraseCredentials();
        $this->assertEquals(null, $this->user->plainPassword);
    }
}