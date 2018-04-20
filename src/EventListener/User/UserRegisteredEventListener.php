<?php
declare(strict_types=1);

namespace App\EventListener\User;

use App\Entity\User;
use App\Event\User\UserRegisteredEvent;
use App\Service\User\ConfirmationTokenService;
use App\Service\User\SendEmailService;

class UserRegisteredEventListener
{
    /**
     * @var SendEmailService
     */
    private $emailService;

    private $confirmationTokenService;

    public function __construct(SendEmailService $emailService, ConfirmationTokenService $confirmationTokenService)
    {
        $this->emailService = $emailService;
        $this->confirmationTokenService = $confirmationTokenService;
    }

    public function onUserRegistered(UserRegisteredEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) { return; }

        $this->sendEmailConfirmation($user);
    }

    private function sendEmailConfirmation(User $user)
    {
        $token = $this->confirmationTokenService->getEmailConfirmationToken($user);

        $this->emailService->sendEmailConfirmation($user, $token->getToken());
    }
}