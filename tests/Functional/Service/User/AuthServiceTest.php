<?php

namespace App\Tests\Functional\Service\User;

use App\Users\DataFixtures\UsersFixtures;
use App\Users\Entity\ApiToken;
use App\Users\Entity\User;
use App\Users\Repository\UserRepository;
use App\Users\Request\AuthUserRequest;
use App\Users\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class AuthServiceTest extends KernelTestCase
{
    /**
     * {@inheritdoc}
     */
    /** @var AuthUserRequest|MockObject */
    private $authUserRequest;

    /**
     * @var UserPasswordEncoderInterface
     */
    protected $passwordEncoder;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();
        $this->authUserRequest = $this->createMock(AuthUserRequest::class);
        $this->passwordEncoder = $kernel->getContainer()->get('security.password_encoder');
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->translator = $kernel->getContainer()->get('translator');
        $this->userRepository = $this->entityManager->getRepository(User::class);
    }

    public function testGetTokenSuccess()
    {
        $authService = new AuthService($this->entityManager, $this->userRepository, $this->translator, $this->passwordEncoder);

        $this->authUserRequest->method('get')->with('credentials')->willReturn([
            'username' => UsersFixtures::TESTER_USERNAME,
            'password' => UsersFixtures::TESTER_PASSWORD,
        ]);

        $result = $authService->getTokenByRequest($this->authUserRequest);

        $this->assertInstanceOf(ApiToken::class, $result);
        $this->assertInstanceOf(User::class, $result->getUser());
        $this->assertNotEmpty($result->getToken());
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
        $this->authUserRequest = null;
    }
}
