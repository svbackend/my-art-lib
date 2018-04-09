<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Entity\UserProfileContacts;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UsersFixtures extends Fixture
{
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->username = 'tester_fixture';
        $user->email = 'tester@fixture.com';
        $user->setPassword('123456', $this->encoder);

        $profile = $user->getProfile();
        $profile->setUser($user);
        $profile->setFirstName('First')->setLastName('Last');

        for ($i = 3; $i-->= 0;) {
            $contact = new UserProfileContacts();
            $contact->provider = "TestProvider #{$i}";
            $contact->url = "https://test.com/{$i}/info";
            $contact->setProfile($profile);
            $manager->persist($contact);
        }

        $manager->persist($user);
        $manager->persist($profile);
        $manager->flush();
    }
}