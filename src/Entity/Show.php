<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ShowRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShowRepository::class)]
#[ApiResource]
class Show
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private readonly ?string $code = null
)
    {
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getLines(): iterable
    {
        return explode("\n", $this->getAsciiCast());
    }

    public function getHeader(): ?array
    {
        return json_decode($this->getLines()[0], true);
    }


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $asciiCast = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getAsciiCast(): ?string
    {
        return $this->asciiCast;
    }

    public function setAsciiCast(string $asciiCast): static
    {
        $this->asciiCast = $asciiCast;

        return $this;
    }
}
