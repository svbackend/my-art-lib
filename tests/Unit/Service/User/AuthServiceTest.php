<?php

namespace App\Tests\Unit\Service\User;

use App\Users\DataFixtures\UsersFixtures;
use App\Users\Entity\ApiToken;
use App\Users\Entity\User;
use App\Users\Repository\UserRepository;
use App\Users\Request\AuthUserRequest;
use App\Users\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Translation\TranslatorInterface;

class AuthServiceTest extends KernelTestCase
{
    /**
     * @var \App\Users\Request\AuthUserRequest|MockObject
     */
    private $authUserRequest;

    /**
     * @var AuthService
     */
    private $authService;

    private $entityManager;

    /**
     * @var $userRepository \App\Users\Repository\UserRepository|MockObject
     */
    private $userRepository;

    private $translator;

    /**
     * @var UserPasswordEncoderInterface|MockObject
     */
    private $passwordEncoder;

    public function setUp()
    {
        $this->authUserRequest = $this->createMock(\App\Users\Request\AuthUserRequest::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        // translator will always return the same message
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->method('trans')->willReturn('translated_message');

        // passwordEncoder would not encode passwords
        $this->passwordEncoder = $this->createMock(UserPasswordEncoderInterface::class);
        $this->passwordEncoder->method('encodePassword')->will($this->returnArgument(0));

        $this->authService = new AuthService($this->entityManager, $this->userRepository, $this->translator, $this->passwordEncoder);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\BadCredentialsException
     */
    public function testGetTokenWithInvalidUsername()
    {
        $credentials = [
            'username' => 'InvalidUsername',
            'password' => '123456'
        ];

        $this->userRepository->method('loadUserByUsername')->willReturn(null);
        $this->authUserRequest->method('get')->with('credentials')->willReturn($credentials);

        $this->authService->getTokenByRequest($this->authUserRequest); // should be exception
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\BadCredentialsException
     */
    public function testGetTokenWithInvalidPassword()
    {
        $credentials = [
            'username' => UsersFixtures::TESTER_USERNAME,
            'password' => 'WrongPassword'
        ];

        $this->passwordEncoder->method('isPasswordValid')->willReturn(false);

        $user = new User('tester@tester.com', 'tester', 'tester');
        $user->setPassword('fakePassword', $this->passwordEncoder);

        $this->userRepository->method('loadUserByUsername')->willReturn($user); // Will return user with fake password
        $this->authUserRequest->method('get')->with('credentials')->willReturn($credentials);

        $this->authService->getTokenByRequest($this->authUserRequest); // should be exception
    }

    public function testGetTokenWithValidPassword()
    {
        $credentials = [
            'username' => UsersFixtures::TESTER_USERNAME,
            'password' => UsersFixtures::TESTER_PASSWORD
        ];

        $this->passwordEncoder->method('isPasswordValid')->willReturn(true);

        $user = new User('tester@tester.com', 'tester', 'tester');
        $user->setPassword(UsersFixtures::TESTER_PASSWORD, $this->passwordEncoder);

        $this->userRepository->method('loadUserByUsername')->willReturn($user); // Will return user with correct password
        $this->authUserRequest->method('get')->with('credentials')->willReturn($credentials);

        $apiToken = $this->authService->getTokenByRequest($this->authUserRequest);

        $this->assertInstanceOf(ApiToken::class, $apiToken);
        $this->assertNotEmpty($apiToken->getToken());
        $this->assertTrue(strlen($apiToken->getToken()) === 256); // not more than 256 symbols due ApiToken entity field limit
    }
}