<?php

namespace App\Tests\Functional\Service\User;

use App\Entity\User;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Request\User\RegisterUserRequest;
use PHPUnit\Framework\MockObject\MockObject;
use App\Service\User\RegisterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegisterServiceTest extends KernelTestCase
{
    /**
     * {@inheritDoc}
     */
    /** @var  RegisterUserRequest|MockObject */
    private $requestObject;

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
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();
        $this->requestObject = $this->createMock(RegisterUserRequest::class);
        $this->passwordEncoder = $kernel->getContainer()->get('security.password_encoder');
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->validator = $kernel->getContainer()->get('validator');
    }

    public function testRegister()
    {
        $registerService = new RegisterService($this->passwordEncoder, $this->entityManager, $this->validator);
        $this->requestObject->method('get')->with('registration')->willReturn([
            'username' => 'registerServiceTester',
            'password' => 'registerServiceTester',
            'email' => 'register@Service.Tester',
        ]);
        $registerServiceResult = $registerService->registerByRequest($this->requestObject);

        echo var_export($registerServiceResult);

        $this->assertContains('id', $registerServiceResult);
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