<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Users\Entity\ApiToken;
use App\Users\Entity\User;
use App\Users\Repository\ApiTokenRepository;
use App\Users\Repository\UserRepository;
use App\Users\Security\UserProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;

class UserProviderTest extends KernelTestCase
{
    /**
     * @var UserProvider
     */
    private $userProvider;

    /**
     * @var \App\Users\Repository\UserRepository|MockObject
     */
    private $userRepositoryMock;

    /**
     * @var \App\Users\Repository\ApiTokenRepository|MockObject
     */
    private $apiTokenRepositoryMock;

    /**
     * @var TranslatorInterface|MockObject
     */
    private $translatorMock;

    public function setUp()
    {
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->apiTokenRepositoryMock = $this->createMock(ApiTokenRepository::class);
        $this->translatorMock = $this->createMock(Translator::class);
        $this->translatorMock->method('trans')->will($this->returnArgument(0));

        $this->userProvider = new \App\Users\Security\UserProvider($this->userRepositoryMock, $this->apiTokenRepositoryMock, $this->translatorMock);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadUserByUsernameFail()
    {
        $this->userRepositoryMock->method('loadUserByUsername')->willReturn(null);
        $this->userProvider->loadUserByUsername('notExistedUser');
    }

    public function testLoadUserByUsernameSuccess()
    {
        $user = new User('tester@tester.com', 'tester', 'tester');
        $this->userRepositoryMock->method('loadUserByUsername')->willReturn($user);
        $result = $this->userProvider->loadUserByUsername('validUsername');
        self::assertInstanceOf(User::class, $result);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadUserByTokenFail()
    {
        $this->apiTokenRepositoryMock->method('findByToken')->willReturn(null);
        $this->userProvider->loadUserByToken('notExistedUserToken');
    }

    public function testLoadUserByTokenSuccess()
    {
        $user = new User('tester@tester.com', 'tester', 'tester');
        $apiToken = new ApiToken($user);
        $this->apiTokenRepositoryMock->method('findByToken')->willReturn($apiToken);
        $result = $this->userProvider->loadUserByToken('validToken');
        self::assertInstanceOf(User::class, $result);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     */
    public function testRefreshUserWithUnsupportedClass()
    {
        /**
         * @var UserInterface
         */
        $unsupportedUserClass = $this->createMock(UserInterface::class);
        $this->userProvider->refreshUser($unsupportedUserClass);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testRefreshUserWhenUserNotFound()
    {
        $user = new User('tester@tester.com', 'tester', 'tester');
        $this->userRepositoryMock->method('find')->willReturn(null);
        $this->userProvider->refreshUser($user);
    }

    public function testRefreshUserSuccess()
    {
        $user = new User('tester@tester.com', 'tester', 'tester');
        $this->userRepositoryMock->method('find')->willReturn($user);
        $result = $this->userProvider->refreshUser($user);
        self::assertInstanceOf(User::class, $result);
    }
}
