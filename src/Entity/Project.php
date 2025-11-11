<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ProjectRepository;
use App\Workflow\IProjectWorkflow;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\MeiliBundle\Api\Filter\FacetsFieldSearchFilter;
use Survos\StateBundle\Traits\MarkingInterface;
use Survos\StateBundle\Traits\MarkingTrait;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ApiResource(
    // ugh, used for meili
    normalizationContext: [
        'groups' => ['project.read', 'rp','translation','marking','_translations'],
    ],
    operations: [
        new Get(
            normalizationContext: ['groups' => ['project.read', 'marking']],
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['project.read', 'marking']],
        )
    ]
)]
#[ApiFilter(OrderFilter::class, properties: ['marking', 'name'], arguments: ['orderParameterName' => 'order'])]
#[ApiFilter(SearchFilter::class, properties: ['marking' => 'exact', 'name' => 'partial'])]
#[ApiFilter(FacetsFieldSearchFilter::class,
    properties: ['tags', 'license','dependencies','extensions','minimumStability'])]

class Project implements \Stringable, MarkingInterface
{
    use MarkingTrait;

    public function __construct()
    {
        $this->marking = IProjectWorkflow::PLACE_NEW;
    }

    // virtual properties

    #[Groups(['project.read'])]
    public ?string $license {
        get => $this->composerJson['license']??null;
    }

    #[Groups(['project.read'])]
    public array $tags {
        get => $this->composerJson['keywords']??[];
    }

    #[Groups(['project.read'])]
    public array $dependencies {
        get => array_values(array_filter(array_keys($this->composerJson['require']??[]), fn(string $key) => str_contains($key, '/')));
    }

    #[Groups(['project.read'])]
    public array $extensions {
        get => array_values(array_filter(array_keys($this->composerJson['require']??[]), fn(string $key) => str_starts_with($key, 'ext-')));
    }

    #[ORM\Id]
    #[ORM\GeneratedValue()]
    #[ORM\Column]
    #[Groups(['project.read'])]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?array $appJson = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['project.read'])]
    private ?array $composerJson = null;

    #[ORM\Column(nullable: true)]
    private ?array $pwaYaml = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $localDir = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $description = null;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $minimumStability = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUpdatedTime = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppJson(): ?array
    {
        return $this->appJson;
    }

    #[Groups(['project.read'])]
    public function setAppJson(?array $appJson): static
    {
        $this->appJson = $appJson;
        $this->description = $appJson['description'] ?? null;

        return $this;
    }

    public function getComposerJson(): ?array
    {
        return $this->composerJson;
    }

    public function setComposerJson(?array $composerJson): static
    {
        $this->composerJson = $composerJson;

        return $this;
    }

    public function getPwaYaml(): ?array
    {
        return $this->pwaYaml;
    }

    public function setPwaYaml(?array $pwaYaml): static
    {
        $this->pwaYaml = $pwaYaml;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    #[Groups(['project.read'])]
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    #[Groups(['project.read'])]
    public function getLiveUrl(): string
    {
        return sprintf("https://%s.survos.com", $this->getName());
    }

    public function getRequireByOwner()
    {
        foreach ($this->getComposerJson()['require'] as $packageName=>$version) {
            if (str_contains($packageName, '/')) {
                [$owner, $name] = explode('/', $packageName);
                if (in_array($owner, ['symfony','twig', 'doctrine','phpdocumentor','phpstan'])) {
                    continue;
                }
                $owners[$owner][] = [
                    'display' => $name, // str_replace('-bundle', '-b', $name),
                    'package' => $packageName
                    ];
            }
        }
        return $owners;

    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    #[Groups(['project.read'])]
    public function getGithubUrl(): string
    {
        return sprintf("https://github.com/%s", $this->getComposerJson()['name']??'!!');

    }

    public function getLocalDir(): ?string
    {
        return $this->localDir;
    }

    public function setLocalDir(?string $localDir): static
    {
        $this->localDir = $localDir;

        return $this;
    }

    #[Groups(['project.read'])]
    public function getMinimumStability(): ?string
    {
        return $this->minimumStability;
    }

    public function setMinimumStability(?string $minimumStability): static
    {
        $this->minimumStability = $minimumStability;

        return $this;
    }

    public function getLastUpdatedTime(): ?\DateTimeImmutable
    {
        return $this->lastUpdatedTime;
    }

    public function setLastUpdatedTime(?\DateTimeImmutable $lastUpdatedTime): static
    {
        $this->lastUpdatedTime = $lastUpdatedTime;

        return $this;
    }
}
