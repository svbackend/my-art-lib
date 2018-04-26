<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WebUiTranslationTest extends WebTestCase
{
    public function testIsTranslationsPageLoads()
    {
        $client = static::createClient();
        $client->request('get', '/admin/_trans');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}