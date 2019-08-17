<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Users\Security\TokenAuthenticator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;

class TokenAuthenticatorTest extends KernelTestCase
{
    /**
     * @var TokenAuthenticator
     */
    private $tokenAuthenticator;

    /**
     * @var TranslatorInterface|MockObject
     */
    private $translatorMock;

    /**
     * @var Request|MockObject
     */
    private $requestMock;

    public function setUp()
    {
        $this->translatorMock = $this->createMock(Translator::class);
        $this->translatorMock->method('trans')->willReturnArgument(0);

        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestQueryMock = $this->createMock(ParameterBag::class);

        $requestMock->query = $requestQueryMock;
        $this->requestMock = $requestMock;

        $this->tokenAuthenticator = new TokenAuthenticator($this->translatorMock);
    }

    public function testSupportsWithoutToken()
    {
        $this->requestMock->query->method('has')->with('api_token')->willReturn(false);
        $result = $this->tokenAuthenticator->supports($this->requestMock);

        self::assertFalse($result);
    }

    public function testSupportsWithEmptyToken()
    {
        $this->requestMock->query->method('has')->with('api_token')->willReturn(true);
        $this->requestMock->query->method('get')->with('api_token')->willReturn('');
        $result = $this->tokenAuthenticator->supports($this->requestMock);

        self::assertFalse($result);
    }

    public function testGetCredentials()
    {
        $this->requestMock->query->method('get')->with('api_token')->willReturn('{token}');
        $result = $this->tokenAuthenticator->getCredentials($this->requestMock);

        self::assertTrue(\is_string($result));
        self::assertSame('{token}', $result);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetUserWithInvalidProvider()
    {
        $invalidUserProvider = $this->createMock(UserProviderInterface::class);
        $this->tokenAuthenticator->getUser('apiToken', $invalidUserProvider);
    }

    public function testSupportsRememberMe()
    {
        $result = $this->tokenAuthenticator->supportsRememberMe();
        self::assertFalse($result);
    }
}
