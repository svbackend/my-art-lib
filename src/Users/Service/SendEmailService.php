<?php

declare(strict_types=1);

namespace App\Users\Service;

use App\Movies\DTO\ReleaseDateNotificationDTO;
use App\Users\Entity\User;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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

    /**
     * @var LoggerInterface|NullLogger
     */
    private $logger;

    /**
     * From email
     * @var string
     */
    private $supportEmail;

    public function __construct(TranslatorInterface $translator, \Swift_Mailer $mailer, \Twig_Environment $twig, ConfirmationTokenService $confirmationTokenService, LoggerInterface $logger)
    {
        $this->translator = $translator;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->confirmationTokenService = $confirmationTokenService;
        $this->supportEmail = \getenv('MAILER_SUPPORT_EMAIL') ?: 'support@mykino.top';
        $this->logger = $logger ?? new NullLogger();
    }

    // todo translations for movie + for email text
    public function sendReleaseDateNotification(string $email, ReleaseDateNotificationDTO $data)
    {
        $body = $this->twig->render(
            'emails/releaseDateNotification.html.twig',
            (array)$data
        );

        $subject = $this->translator->trans('release_date_notification_email_subject', [
            '{movieTitle}' => $data->movieTitle,
            '{countryName}' => $data->countryName,
        ], 'users');

        $this->sendEmail($email, $subject, $body);
    }

    public function sendEmailConfirmation(User $user)
    {
        $emailConfirmationToken = $this->confirmationTokenService->getEmailConfirmationToken($user)->getToken();

        $body = $this->twig->render(
            'emails/confirmEmail.html.twig',
            ['token' => $emailConfirmationToken]
        );

        $subject = $this->translator->trans('user_registration_email_subject', [
            '{appName}' => \getenv('APP_NAME'),
        ], 'users');

        $this->sendEmail($user->getEmail(), $subject, $body);
    }

    public function sendPasswordRecoveryConfirmation(User $user)
    {
        $passwordRecoveryToken = $this->confirmationTokenService->getPasswordRecoveryToken($user)->getToken();

        $body = $this->twig->render(
            'emails/passwordRecovery.html.twig',
            ['token' => $passwordRecoveryToken]
        );

        $subject = $this->translator->trans('user_password_recovery_email_subject', [], 'users');

        $this->sendEmail($user->getEmail(), $subject, $body);
    }

    private function sendEmail($recipientEmail, string $subject, string $body)
    {
        $this->logger->info("[MAILER] Trying to send email to {$recipientEmail} with next params:", [
            'subject' => $subject,
            'body' => $body,
        ]);
        $message = (new \Swift_Message($subject))
            ->setFrom($this->supportEmail)
            ->setTo($recipientEmail)
            ->setBody($body, 'text/html');

        // todo need to retry if any failed recipients found ($failedRecipients is array of emails)
        $failedRecipients = [];
        $this->mailer->send($message, $failedRecipients);

        if (count($failedRecipients)) {
            $this->logger->warning('[MAILER] Some of mails not sent, list of all recipients: ', $failedRecipients);
        }
    }
}
