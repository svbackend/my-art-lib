<?php

namespace App\Tests\Functional\Service\User;

use App\Entity\User;
use Doctrine\DBAL\Schema\Constraint;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Request\User\RegisterUserRequest;
use PHPUnit\Framework\MockObject\MockObject;
use App\Service\User\RegisterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class RegisterServiceTest extends KernelTestCase
{
    /**
     * {@inheritDoc}
     */
    /** @var  RegisterUserRequest|MockObject */
    private $registerUserRequest;

    /**
     * @var RegisterService
     */
    #private $registerService;

    /**
     * @var UserPasswordEncoderInterface
     */
    protected $passwordEncoder;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();
        $this->registerUserRequest = $this->createMock(RegisterUserRequest::class);
        $this->passwordEncoder = $kernel->getContainer()->get('security.password_encoder');
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->validator = $kernel->getContainer()->get('validator');
        $this->dispatcher = $kernel->getContainer()->get('event_dispatcher');
    }

    public function testSuccessRegister()
    {
        $registerService = new RegisterService($this->entityManager, $this->validator, $this->dispatcher);
        $this->registerUserRequest->method('get')->with('registration')->willReturn([
            'username' => 'registerServiceTester',
            'password' => 'registerServiceTester',
            'email' => 'register@Service.Tester',
        ]);
        $registerServiceResult = $registerService->registerByRequest($this->registerUserRequest);

        $this->assertInstanceOf(User::class, $registerServiceResult);
        $this->assertNotEmpty($registerServiceResult->getId(), 'User Id not provided.');
    }

    public function testInvalidRegister()
    {
        $registerService = new RegisterService($this->entityManager, $this->validator, $this->dispatcher);
        $this->registerUserRequest->method('get')->with('registration')->willReturn([
            'username' => 'registerServiceTester',
            'password' => 'registerServiceTester',
            'email' => 'register@Service.Tester',
        ]);
        // Here we register our user successfully
        $registerService->registerByRequest($this->registerUserRequest);

        $this->registerUserRequest->method('get')->with('registration')->willReturn([
            'username' => 'registerServiceTester',
            'password' => 'registerServiceTester',
            'email' => 'register@Service.Tester',
        ]);
        $this->registerUserRequest->method('getErrorResponse')->will($this->returnArgument(0)); // will return errors
        // But here this user should not be registered again because he already exist in DB
        $registerServiceResult = $registerService->registerByRequest($this->registerUserRequest);

        $this->assertInstanceOf(ConstraintViolationListInterface::class, $registerServiceResult);
        $this->assertTrue(count($registerServiceResult) > 0);
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