<?php
declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Users\Entity\User;
use App\Users\Entity\UserRoles;
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

        $this->user = new User('tester@tester.com', 'tester', 'tester');
        $this->passwordEncoder = $kernel->getContainer()->get('security.password_encoder');
    }
    
    public function testGetId()
    {
        $this->assertEmpty($this->user->getId());
    }

    public function testGetRoles()
    {
        $this->assertEquals([UserRoles::ROLE_USER], $this->user->getRoles());
    }

    public function testAddRole()
    {
        $this->user->getRolesObject()->addRole(UserRoles::ROLE_MODERATOR);
        $this->assertEquals([UserRoles::ROLE_USER, UserRoles::ROLE_MODERATOR], $this->user->getRoles());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddInvalidRole()
    {
        $this->user->getRolesObject()->addRole('InvalidRole');
    }

    public function testRemoveRole()
    {
        $this->user->getRolesObject()->removeRole(UserRoles::ROLE_USER);
        $this->assertEquals([UserRoles::ROLE_USER], $this->user->getRoles());

        $this->user->getRolesObject()->addRole(UserRoles::ROLE_MODERATOR);
        $this->user->getRolesObject()->removeRole(UserRoles::ROLE_USER);
        $this->assertEquals([UserRoles::ROLE_MODERATOR], $this->user->getRoles());

        $this->user->getRolesObject()->removeRole(UserRoles::ROLE_MODERATOR);
        $this->assertEquals([UserRoles::ROLE_USER], $this->user->getRoles());
    }

    public function testIsPasswordValid()
    {
        $this->user->setPassword('123456', $this->passwordEncoder);
        $this->assertEquals(false, $this->user->isPasswordValid('wrongPassword', $this->passwordEncoder));
        $this->assertEquals(true, $this->user->isPasswordValid('123456', $this->passwordEncoder));
    }

    public function testDefaultRoles()
    {
        $defaultRoles = $this->user->getRolesObject()->getDefaultRoles();

        self::assertTrue(count($defaultRoles) > 0);
    }

    public function testValidRoles()
    {
        $validRoles = $this->user->getRolesObject()->getValidRoles();

        self::assertTrue(count($validRoles) > 0);
    }
}