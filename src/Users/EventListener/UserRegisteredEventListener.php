<?php
declare(strict_types=1);

namespace App\Users\EventListener;

use App\Users\Entity\User;
use App\Users\Event\UserRegisteredEvent;
use App\Users\Service\SendEmailService;

class UserRegisteredEventListener
{
    /**
     * @var SendEmailService
     */
    private $emailService;

    public function __construct(SendEmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function onUserRegistered(UserRegisteredEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) { return; }

        $this->sendEmailConfirmation($user);
    }

    private function sendEmailConfirmation(User $user)
    {
        $this->emailService->sendEmailConfirmation($user);
    }
}