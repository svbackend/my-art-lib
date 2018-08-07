<?php
declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Users\Entity\ConfirmationToken;
use App\Users\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ConfirmationTokenTest extends KernelTestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructWithInvalidType()
    {
        $user = $this->createMock(User::class);
        $confirmationToken = new ConfirmationToken($user, 'invalidTokenType');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructWithExpiredToken()
    {
        $user = $this->createMock(User::class);
        $expires_at = new \DateTimeImmutable('-1 hour'); // already expired token
        $confirmationToken = new ConfirmationToken($user, ConfirmationToken::TYPE_CONFIRM_EMAIL, $expires_at);
    }
}