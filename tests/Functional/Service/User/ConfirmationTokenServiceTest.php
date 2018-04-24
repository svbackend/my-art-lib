<?php

namespace App\Tests\Functional\Service\User;

use App\Users\DataFixtures\UsersFixtures;
use App\Users\Entity\ConfirmationToken;
use App\Users\Entity\User;
use App\Users\Repository\ConfirmationTokenRepository;
use App\Users\Service\ConfirmationTokenService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Doctrine\ORM\EntityManagerInterface;

class ConfirmationTokenServiceTest extends KernelTestCase
{
    /**
     * @var UserPasswordEncoderInterface
     */
    protected $passwordEncoder;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testEmailConfirmationTokenSuccessfullySaved()
    {
        $user = $this->entityManager->getRepository(User::class)->loadUserByUsername(UsersFixtures::TESTER_USERNAME);

        $confirmationTokenService = new ConfirmationTokenService($this->entityManager);
        $emailConfirmationToken = $confirmationTokenService->getEmailConfirmationToken($user);

        /**
         * @var $confirmationTokenRepository ConfirmationTokenRepository
         */
        $confirmationTokenRepository = $this->entityManager->getRepository(ConfirmationToken::class);
        $savedToken = $confirmationTokenRepository->findByToken($emailConfirmationToken->getToken());

        self::assertNotNull($savedToken);
        self::assertInstanceOf(ConfirmationToken::class, $savedToken);
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