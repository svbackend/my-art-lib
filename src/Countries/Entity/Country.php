<?php

declare(strict_types=1);

namespace App\Countries\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Countries\Repository\CountryRepository")
 * @ORM\Table(name="countries")
 * @UniqueEntity(fields="code", message="This code already exists")
 */
class Country
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=3, unique=true)
     * @Groups({"list", "view"})
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=50, unique=true)
     * @Groups({"list", "view"})
     */
    private $name;

    public function __construct(string $name, string $code)
    {
        // todo movie to db constraint
        if (mb_strlen($code) !== 3) {
            // throw new \InvalidArgumentException(sprintf('"%s" should be exactly 3 characters long', $code));
        }

        $this->code = mb_strtoupper($code);
        $this->name = ucfirst($name);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
