<?php

declare(strict_types=1);

namespace App\Users\EventListener;

use App\Users\Entity\User;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserEntityEventListener
{
    private $userPasswordEncoder;

    public function __construct(UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $this->process($event);
    }

    public function preUpdate(LifecycleEventArgs $event): void
    {
        $this->process($event);
    }

    private function process(LifecycleEventArgs $event): void
    {
        // Get user entity object
        $user = $event->getObject();

        // Valid user so lets change password
        if ($user instanceof User) {
            $this->changePassword($user);
        }
    }

    private function changePassword(User $user): void
    {
        $plainPassword = $user->getPlainPassword();

        if (!empty($plainPassword)) {
            $user->setPassword($plainPassword, $this->userPasswordEncoder);
            $user->eraseCredentials();
        }
    }
}
