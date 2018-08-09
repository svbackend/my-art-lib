<?php

namespace App\Tests\Functional\Repository;

use App\Users\Entity\User;
use App\Users\Repository\UserRepository;
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
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();

        $this->user = new User('tester@tester.com', 'tester', 'tester');
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->passwordEncoder = $kernel->getContainer()->get('security.password_encoder');
    }

    protected function createUser($email, $username, $password)
    {
        $this->user = new User($email, $username, $password);

        try {
            $this->entityManager->persist($this->user);
            $this->entityManager->flush();
        } catch (ORMException $e) {
            $debugData = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
            $this->fail('User not saved, error: '.var_export($debugData));
        }

        return $this->user;
    }

    public function testFindAll()
    {
        $createdUser = $this->createUser('tester@tester.com', 'tester', '123456');
        $users = $this->entityManager->getRepository(User::class)->findAll();

        $this->assertTrue(is_array($users));
        $this->assertTrue(count($users) > 0);
        $this->assertContains($createdUser, $users);
    }

    public function testLoadByUsernameExistedUser()
    {
        /**
         * @var UserRepository
         */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->loadUserByUsername('tester_fixture');

        $this->assertNotNull($user);
        $this->assertObjectHasAttribute('username', $user);
        $this->assertObjectHasAttribute('email', $user);
    }

    public function testLoadByUsernameNonExistedUser()
    {
        /**
         * @var UserRepository
         */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->loadUserByUsername('NotExistedUser');

        $this->assertNull($user);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }
}
