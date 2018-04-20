<?php
declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User;
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

    public function __construct(TranslatorInterface $translator, \Swift_Mailer $mailer, \Twig_Environment $twig)
    {
        $this->translator = $translator;
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function sendEmailConfirmation(User $user, string $confirmationToken)
    {
        $body = $this->twig->render(
            'emails/confirmEmail.html.twig',
            ['token' => $confirmationToken]
        );

        $subject = $this->translator->trans('user_registration_email_subject', [], 'users');

        $this->sendEmail($user->email, $subject, $body);
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