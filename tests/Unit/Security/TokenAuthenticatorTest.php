<?php
declare(strict_types=1);

namespace App\Tests\Unit\Security;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Translation\Translator;
use App\Users\Security\TokenAuthenticator;

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
        $this->translatorMock->method('trans')->will($this->returnArgument(0));

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

        self::assertTrue(is_string($result));
        self::assertEquals('{token}', $result);
    }
}