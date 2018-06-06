<?php

declare(strict_types=1);

namespace App\Users\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
class UserRoles
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_MODERATOR = 'ROLE_MODER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $roles;

    public function __construct()
    {
        $this->addRole(self::ROLE_USER);
    }

    public function addRole(string $role): self
    {
        if (false === in_array($role, $this->getValidRoles(), true)) {
            throw new \InvalidArgumentException(sprintf('Invalid role: %s', $role));
        }

        if (false === array_search($role, $this->getRoles(), true)) {
            return $this->setRoles(
                array_merge($this->getRoles(), [$role])
            );
        }

        return $this;
    }

    public function removeRole(string $role): self
    {
        $roles = $this->getRoles();
        $foundedRoleKey = array_search($role, $roles, true);

        if (false !== $foundedRoleKey) {
            unset($roles[$foundedRoleKey]);

            return $this->setRoles($roles);
        }

        return $this;
    }

    private function setRoles(array $roles): self
    {
        if (!count($roles)) {
            $this->roles = null;

            return $this;
        }

        $roles = json_encode($roles);

        if (mb_strlen($roles) > 255) {
            throw new \InvalidArgumentException(sprintf('UserRoles $roles is too long. Max 255 characters.'));
        }

        $this->roles = $roles;

        return $this;
    }

    public function getRoles(): array
    {
        if (!$this->roles) {
            return $this->getDefaultRoles();
        }

        $roles = (array) json_decode($this->roles);

        if (!count($roles)) {
            return $this->getDefaultRoles();
        }

        return array_values($roles);
    }

    public function getDefaultRoles(): array
    {
        return [self::ROLE_USER];
    }

    public function getValidRoles(): array
    {
        return [self::ROLE_ADMIN, self::ROLE_MODERATOR, self::ROLE_USER];
    }
}
