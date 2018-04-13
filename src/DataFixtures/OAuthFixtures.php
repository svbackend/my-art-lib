<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\OAuthServerBundle\Model\ClientManagerInterface;

class OAuthFixtures extends Fixture
{
    private $OAuthClientManager;

    public function __construct(ClientManagerInterface $OAuthClientManager)
    {
        $this->OAuthClientManager = $OAuthClientManager;
    }

    public function load(ObjectManager $manager)
    {
        $clientManager = $this->OAuthClientManager;
        $client = $clientManager->createClient();
        $client->setRedirectUris(['http://127.0.0.1:8080/']);
        $client->setAllowedGrantTypes(['password']);
        $clientManager->updateClient($client);
    }
}