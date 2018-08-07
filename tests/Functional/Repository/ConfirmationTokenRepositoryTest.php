<?php

namespace App\Tests\Functional\Repository;

use App\Users\Entity\ConfirmationToken;
use App\Users\Entity\User;
use App\Users\Repository\UserRepository;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ConfirmationTokenRepositoryTest extends KernelTestCase
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
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->passwordEncoder = $kernel->getContainer()->get('security.password_encoder');

        $this->user = new User('tester@tester.com', 'tester', 'tester');
        $this->entityManager->persist($this->user);
    }

    protected function createUser()
    {
        return $this->user;
    }

    private function createToken(User $user, \DateTimeImmutable $expires_at = null)
    {
        $token = new ConfirmationToken($user, ConfirmationToken::TYPE_CONFIRM_EMAIL, $expires_at);
        $this->entityManager->persist($token);
        return $token;
    }

    public function testFindAll()
    {
        $createdUser = $this->createUser();
        $createdToken = $this->createToken($createdUser);
        $this->entityManager->flush();

        $tokens = $this->entityManager->getRepository(ConfirmationToken::class)->findAll();

        $this->assertTrue(is_array($tokens));
        $this->assertTrue(count($tokens) > 0);
        $this->assertContains($createdToken, $tokens);
    }

    public function testIsTokenNotExpired()
    {
        $createdUser = $this->createUser();
        $expires_at = new \DateTimeImmutable('+1 day');
        $createdToken = $this->createToken($createdUser, $expires_at);
        $this->entityManager->flush();

        $tokens = $this->entityManager->getRepository(ConfirmationToken::class)->findAll();
        $foundedToken = array_pop($tokens);
        $this->assertTrue($createdToken->isValid());
        $this->assertTrue($foundedToken->isValid());
    }

    public function testIsTokenGetExpired()
    {
        $createdUser = $this->createUser();
        $createdToken = $this->createToken($createdUser);

        $expires_at = new \DateTimeImmutable('-1 day'); // expired token
        $setExpiredAt = function () use ($expires_at) { $this->expires_at = $expires_at; };
        $setExpiredAt->bindTo($createdToken, get_class($createdToken))();

        $this->entityManager->flush();

        $tokens = $this->entityManager->getRepository(ConfirmationToken::class)->findAll();
        $foundedToken = array_pop($tokens);
        $this->assertFalse($createdToken->isValid());
        $this->assertFalse($foundedToken->isValid());
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