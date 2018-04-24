<?php
declare(strict_types=1);

namespace App\Users\Service;

use App\Users\Entity\User;
use App\Users\Service\ConfirmationTokenService;
use Symfony\Component\Translation\TranslatorInterface;

class SendEmailService
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var ConfirmationTokenService
     */
    private $confirmationTokenService;

    public function __construct(TranslatorInterface $translator, \Swift_Mailer $mailer, \Twig_Environment $twig, ConfirmationTokenService $confirmationTokenService)
    {
        $this->translator = $translator;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->confirmationTokenService = $confirmationTokenService;
    }

    public function sendEmailConfirmation(User $user)
    {
        $emailConfirmationToken = $this->confirmationTokenService->getEmailConfirmationToken($user)->getToken();

        $body = $this->twig->render(
            'emails/confirmEmail.html.twig',
            ['token' => $emailConfirmationToken]
        );

        $subject = $this->translator->trans('user_registration_email_subject', [], 'users');

        $this->sendEmail($user->getEmail(), $subject, $body);
    }

    private function sendEmail($recipientEmail, string $subject, string $body)
    {
        $message = (new \Swift_Message($subject))
            ->setFrom('send@example.com')
            ->setTo($recipientEmail)
            ->setBody($body, 'text/html');

        // todo need to retry if any failed recipients found ($failedRecipients is array of emails)
        $this->mailer->send($message, $failedRecipients);
    }
}