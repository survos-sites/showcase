<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ShowRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ShowRepository::class)]
#[ApiResource(
    // ugh, used for meili
    normalizationContext: [
        'groups' => ['show.read', 'rp','translation','marking','_translations'],
    ],
    operations: [
        new Get(
            normalizationContext: ['groups' => ['show.read', 'marking']],
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['show.read', 'marking']],
        )
    ]
)]
#[Groups(['show.read'])]
class Show
{
    public function __construct(
        #[ORM\Id] #[ORM\Column]
        private readonly ?string $code = null
)
    {
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getLines(): array
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

    #[ORM\Column]
    private ?int $fileSize = null;

    #[ORM\Column]
    private ?int $markerCount = null;

    #[ORM\Column]
    private ?int $lineCount = null;

    #[ORM\Column]
    private ?float $totalTime = null;

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

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): static
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function getMarkerCount(): ?int
    {
        return $this->markerCount;
    }

    public function setMarkerCount(int $markerCount): static
    {
        $this->markerCount = $markerCount;

        return $this;
    }

    public function getLineCount(): ?int
    {
        return $this->lineCount;
    }

    public function setLineCount(int $lineCount): static
    {
        $this->lineCount = $lineCount;

        return $this;
    }

    public function getTotalTime(): ?float
    {
        return $this->totalTime;
    }

    public function setTotalTime(float $totalTime): static
    {
        $this->totalTime = $totalTime;

        return $this;
    }
}
