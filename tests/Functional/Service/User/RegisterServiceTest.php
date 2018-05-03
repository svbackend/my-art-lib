<?php

namespace App\Tests\Functional\Service\User;

use App\Users\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Users\Request\RegisterUserRequest;
use PHPUnit\Framework\MockObject\MockObject;
use App\Users\Service\RegisterService;
use Doctrine\ORM\EntityManagerInterface;

class RegisterServiceTest extends KernelTestCase
{
    /**
     * {@inheritDoc}
     */
    /** @var  RegisterUserRequest|MockObject */
    private $registerUserRequest;

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
        $this->registerUserRequest = $this->createMock(RegisterUserRequest::class);
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testSuccessRegister()
    {
        $registerService = new RegisterService();
        $this->registerUserRequest->method('get')->with('registration')->willReturn([
            'username' => 'registerServiceTester',
            'password' => 'registerServiceTester',
            'email' => 'register@Service.Tester',
        ]);
        $registeredUser = $registerService->registerByRequest($this->registerUserRequest);

        $this->assertInstanceOf(User::class, $registeredUser);
        $this->assertNotEmpty($registeredUser->getUsername(), 'Username not provided.');
        $this->assertNotEmpty($registeredUser->getEmail(), 'Email not provided.');
        $this->assertEquals('registerServiceTester', $registeredUser->getUsername());
        $this->assertEquals('register@Service.Tester', $registeredUser->getEmail());
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
        $this->registerUserRequest = null;
    }
}