<?php

namespace App\Tests\Functional\Repository;

use App\Entity\User;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserRepositoryTest extends KernelTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var User
     */
    private $user;

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

        $this->user = new User();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->passwordEncoder = $kernel->getContainer()->get('security.password_encoder');
    }

    protected function createUser($email, $username, $password)
    {
        $this->user->email = $email;
        $this->user->username = $username;
        $this->user->setPassword($password, $this->passwordEncoder);

        try {
            $this->entityManager->persist($this->user);
            $this->entityManager->flush();
        } catch (ORMException $e) {
            $this->fail('User not saved, error: ' . $e->getMessage());
        }

        return $this->user;
    }

    public function testFindAll()
    {
        $this->createUser('tester@tester.com', 'tester', '123456');
        $users = $this->entityManager->getRepository(User::class)->findAll();

        $this->assertTrue(is_array($users));
        $this->assertTrue(count($users) > 0);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }
}