<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiDocTest extends WebTestCase
{
    public function testIsDocsPageLoads()
    {
        $client = static::createClient();
        $client->request('get', '/api/doc');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}