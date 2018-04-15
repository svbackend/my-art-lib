<?php
declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Repository\ApiTokenRepository;
use App\Repository\UserRepository;
use App\Security\UserProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserProviderTest extends KernelTestCase
{
    /**
     * @var UserProviderInterface
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
}