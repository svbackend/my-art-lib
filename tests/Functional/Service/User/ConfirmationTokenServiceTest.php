<?php

namespace App\Tests\Functional\Service\User;

use App\Users\DataFixtures\UsersFixtures;
use App\Users\Entity\ConfirmationToken;
use App\Users\Entity\User;
use App\Users\Repository\ConfirmationTokenRepository;
use App\Users\Service\ConfirmationTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

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
     * {@inheritdoc}
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
         * @var ConfirmationTokenRepository
         */
        $confirmationTokenRepository = $this->entityManager->getRepository(ConfirmationToken::class);
        $savedToken = $confirmationTokenRepository->findByToken($emailConfirmationToken->getToken());

        self::assertNotNull($savedToken);
        self::assertInstanceOf(ConfirmationToken::class, $savedToken);
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
