<?php
declare(strict_types=1);

namespace App\Event\User;

use Symfony\Component\EventDispatcher\Event;
use App\Entity\User;

class UserRegisteredEvent extends Event
{
    const NAME = 'user.registered';
    const TYPE_DEFAULT_REGISTRATION = 'default';

    protected $user;
    protected $type;

    public function __construct(User $user, string $type = self::TYPE_DEFAULT_REGISTRATION)
    {
        $this->user = $user;
        $this->type = $type;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRegistrationType(): string
    {
        return $this->type;
    }
}