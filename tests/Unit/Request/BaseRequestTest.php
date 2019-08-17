<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Request\BaseRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class BaseRequestTest extends KernelTestCase
{
    /**
     * @var ConstraintViolationList
     */
    private $errors;

    /**
     * @var ConstraintViolation
     */
    private $error;

    /**
     * @var BaseRequest
     */
    private $baseRequest;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->errors = new ConstraintViolationList();
        $this->error = new ConstraintViolation('message', null, [], 'root', 'path', 'invalidValue');
        $this->errors->add($this->error);
        $this->baseRequest = new BaseRequest();
    }

    public function testGetErrorResponse()
    {
        $result = $this->baseRequest->getErrorResponse($this->errors);
        self::assertInstanceOf(JsonResponse::class, $result);

        $resultArray = json_decode($result->getContent(), true);
        self::assertArrayHasKey('errors', $resultArray);
        self::assertArrayHasKey('message', $resultArray);
        self::assertTrue(\is_array($resultArray['errors']));
        self::assertCount(1, $resultArray['errors']);
    }
}
