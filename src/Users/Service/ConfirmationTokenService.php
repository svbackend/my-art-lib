<?php

declare(strict_types=1);

namespace App\Users\Service;

use App\Users\Entity\ConfirmationToken;
use App\Users\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ConfirmationTokenService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getEmailConfirmationToken(User $user): ConfirmationToken
    {
        $expires_at = new \DateTimeImmutable('+14 days');

        return $this->getToken($user, ConfirmationToken::TYPE_CONFIRM_EMAIl, $expires_at);
    }

    private function getToken(User $user, string $type, \DateTimeInterface $expires_at = null): ConfirmationToken
    {
        $confirmationToken = new ConfirmationToken($user, $type, $expires_at);

        $this->entityManager->persist($confirmationToken);
        $this->entityManager->flush();

        return $confirmationToken;
    }
}
