<?php
declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\ApiToken;
use App\Entity\User;
use App\Repository\ApiTokenRepository;
use App\Repository\UserRepository;
use App\Security\UserProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserProviderTest extends KernelTestCase
{
    /**
     * @var UserProvider
     */
    private $userProvider;

    /**
     * @var UserRepository|MockObject
     */
    private $userRepositoryMock;

    /**
     * @var ApiTokenRepository|MockObject
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

        $this->userProvider = new UserProvider($this->userRepositoryMock, $this->apiTokenRepositoryMock, $this->translatorMock);
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
        $user = new User();
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
        $user = new User();
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
         * @var $unsupportedUserClass UserInterface
         */
        $unsupportedUserClass = $this->createMock(UserInterface::class);
        $this->userProvider->refreshUser($unsupportedUserClass);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testRefreshUserWhenUserNotFound()
    {
        $user = new User();
        $this->userRepositoryMock->method('find')->willReturn(null);
        $this->userProvider->refreshUser($user);
    }

    public function testRefreshUserSuccess()
    {
        $user = new User();
        $this->userRepositoryMock->method('find')->willReturn($user);
        $result =  $this->userProvider->refreshUser($user);
        self::assertInstanceOf(User::class, $result);
    }
}