<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ShowRepository;
use App\Workflow\ShowWFDefinition;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\StateBundle\Traits\MarkingInterface;
use Survos\StateBundle\Traits\MarkingTrait;
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
class Show implements MarkingInterface, \Stringable
{
    use MarkingTrait;
    public function __construct(
        #[ORM\Id] #[ORM\Column]
        private readonly ?string $code = null
)
    {
        $this->marking = ShowWFDefinition::PLACE_NEW;
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
    public ?string $title = null;

    #[ORM\Column(nullable: true)]
    public ?string $author = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $asciiCast = null;

    #[ORM\Column(nullable: true)]
    public ?int $fileSize = null;

    #[ORM\Column(nullable: true)]
    public ?int $asciinamaId = null;

    public ?string $castUrl { get => $this->asciinamaId ? 'https://asciinema.org/a/' . $this->asciinamaId : null; }
    public ?string $downloadUrl { get => $this->asciinamaId ? 'https://asciinema.org/a/' . $this->asciinamaId  . '.cast': null; }



//    #[ORM\Column]
//    #[ApiProperty("duration in seconds")]
//    public ?int $duration = null;

    #[ORM\Column]
    public ?int $markerCount = null;

    #[ORM\Column]
    public ?int $lineCount = null;

    #[ORM\Column(nullable: true)]
    #[ApiProperty("the number of type 'i' in the file")]
    public int $inputCount = 0;

    #[ORM\Column]
    public ?float $totalTime = null;

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

    public function __toString()
    {
        return $this->code;
    }
}
