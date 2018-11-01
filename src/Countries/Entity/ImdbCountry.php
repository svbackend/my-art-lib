<?php

declare(strict_types=1);

namespace App\Countries\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Countries\Repository\ImdbCountryRepository")
 * @ORM\Table(name="imdb_countries")
 * @UniqueEntity(fields="name", message="This country already exists")
 */
class ImdbCountry
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="App\Countries\Entity\Country")
     * @ORM\JoinColumn(nullable=false)
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=50, unique=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $altNames;

    public function __construct(Country $country, string $name, string $altNames = '')
    {
        $this->country = $country;
        $this->name = $name;
        $this->altNames = $altNames;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCountry(): Country
    {
        return $this->country;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAltNames(): string
    {
        return $this->altNames;
    }
}
