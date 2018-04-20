<?php
declare(strict_types=1);

namespace App\EventListener\User;

use App\Entity\User;
use App\Event\User\UserRegisteredEvent;

class UserRegisteredEventListener
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    public function onUserRegistered(UserRegisteredEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) { return; }

        $this->sendEmailConfirmation($user);
    }

    private function sendEmailConfirmation(User $user)
    {
        // Move this logic to service
        /*
        $message = (new \Swift_Message('Confirm your email on my art lib'))
            ->setFrom('send@example.com')
            ->setTo($user->email)
            ->setBody(
                $this->twig->render(
                    'emails/registration.html.twig',
                    array('name' => $name)
                ),
                'text/html'
            );

        $mailer->send($message);
        */

    }
}