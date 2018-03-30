<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
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
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();

        $this->user = new User();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
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
        $this->assertEquals(null, $this->user->plainPassword);
    }
}