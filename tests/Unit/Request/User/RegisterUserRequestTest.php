<?php
declare(strict_types=1);

namespace App\Tests\Unit\Request\User;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Request\User\RegisterUserRequest;
use Symfony\Component\Validator\Constraints as Assert;

class RegisterUserRequestTest extends KernelTestCase
{
    /**
     * @var RegisterUserRequest
     */
    private $request;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->request = new RegisterUserRequest();
    }

    public function testRules()
    {
        $rules = $this->request->rules();
        self::assertInstanceOf(Assert\Collection::class, $rules);
    }
}