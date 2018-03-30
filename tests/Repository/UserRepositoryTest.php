<?php

namespace App\Tests\Repository;

use App\Entity\User;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

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
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->user = new User;
    }

    protected function createUser($email, $username, $password)
    {
        $this->user->email = $email;
        $this->user->username = $username;
        $this->user->plainPassword = $password;

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
        $this->createUser('tester@tester.com', 'unitTester', '123456');
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