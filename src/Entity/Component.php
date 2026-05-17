<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\ComponentKind;
use App\Repository\ComponentRepository;
use App\Workflow\ComponentFlow;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\FieldBundle\Attribute\EntityMeta;
use Survos\FieldBundle\Attribute\Field;
use Survos\FieldBundle\Attribute\RouteIdentity;
use Survos\FieldBundle\Entity\RouteIdentityTrait;
use Survos\CoreBundle\Entity\RouteParametersInterface;
use Survos\MeiliBundle\Api\Filter\FacetsFieldSearchFilter;
use Survos\MeiliBundle\Metadata\MeiliIndex;
use Survos\StateBundle\Traits\MarkingInterface;
use Survos\StateBundle\Traits\MarkingTrait;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ComponentRepository::class)]
#[EntityMeta(icon: 'tabler:package', group: 'Components', description: 'Survos/Museado repos, bundles, and libraries')]
#[RouteIdentity(field: 'id')]
#[ApiResource(
    normalizationContext: ['groups' => ['component.read', 'marking']],
    operations: [
        new Get(uriTemplate: '/components/{code}'),
        new GetCollection(uriTemplate: '/components'),
    ],
)]
#[ApiFilter(OrderFilter::class, properties: ['name', 'marking', 'minimumStability'])]
#[ApiFilter(SearchFilter::class, properties: ['marking' => 'exact', 'name' => 'partial'])]
#[ApiFilter(FacetsFieldSearchFilter::class, properties: ['tags', 'license', 'dependencies', 'extensions', 'minimumStability', 'kind'])]
#[MeiliIndex(filterable: ['minimumStability', 'kind'])]
final class Component implements \Stringable, MarkingInterface, RouteParametersInterface
{
    use MarkingTrait;
    use RouteIdentityTrait;

    // PK: composer name with / replaced by __, e.g. "survos__jsonl-bundle"
    #[ORM\Id]
    #[ORM\Column(length: 128)]
    #[Groups(['component.read'])]
    #[Field(searchable: true, sortable: true, order: 1)]
    public readonly string $id;

    // full composer/github name, e.g. "survos/jsonl-bundle"
    #[ORM\Column(length: 255)]
    #[Groups(['component.read'])]
    #[Field(searchable: true, order: 2)]
    public string $composerName;

    #[ORM\Column(nullable: true)]
    #[Groups(['component.read'])]
    #[Field(filterable: true, facet: true, order: 5)]
    public ?ComponentKind $kind = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['component.read'])]
    #[Field(searchable: true, sortable: true, order: 3)]
    public ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['component.read'])]
    #[Field(searchable: true, order: 10)]
    public ?string $description = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['component.read'])]
    #[Field(filterable: true, facet: true, order: 20)]
    public ?string $minimumStability = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['component.read'])]
    public ?string $overview = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['component.read'])]
    public ?string $plan = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $localDir = null;

    #[ORM\Column(nullable: true)]
    public ?array $composerJson = null;

    #[ORM\Column(nullable: true)]
    public ?array $pwaYaml = null;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $lastUpdatedTime = null;

    // ── Computed / virtual ────────────────────────────────────────────────────

    #[Groups(['component.read'])]
    #[Field(filterable: true, facet: true, order: 6)]
    public ?string $license {
        get => $this->composerJson['license'] ?? null;
    }

    #[Groups(['component.read'])]
    #[Field(filterable: true, facet: true, order: 7)]
    public array $tags {
        get => $this->composerJson['keywords'] ?? [];
    }

    #[Groups(['component.read'])]
    #[Field(filterable: true, order: 8)]
    public array $dependencies {
        get => array_values(array_filter(
            array_keys($this->composerJson['require'] ?? []),
            fn(string $k) => str_contains($k, '/')
        ));
    }

    #[Groups(['component.read'])]
    public array $extensions {
        get => array_values(array_filter(
            array_keys($this->composerJson['require'] ?? []),
            fn(string $k) => str_starts_with($k, 'ext-')
        ));
    }

    #[Groups(['component.read'])]
    public string $githubUrl {
        get {
            assert($this->composerName !== null, 'composerName must be set before accessing githubUrl');
            return sprintf('https://github.com/%s', $this->composerName);
        }
    }

    public array $requireByOwner {
        get {
            $owners = [];
            foreach ($this->composerJson['require'] ?? [] as $packageName => $version) {
                if (!str_contains($packageName, '/')) {
                    continue;
                }
                [$owner, $pkg] = explode('/', $packageName, 2);
                if (in_array($owner, ['symfony', 'twig', 'doctrine', 'phpdocumentor', 'phpstan'], true)) {
                    continue;
                }
                $owners[$owner][] = ['display' => $pkg, 'package' => $packageName];
            }

            return $owners;
        }
    }

    public function __construct(string $composerName)
    {
        $this->id = str_replace('/', '__', $composerName);
        $this->composerName = $composerName;
        $this->marking = ComponentFlow::PLACE_NEW;
    }

    public function __toString(): string
    {
        return $this->name ?? $this->code;
    }
}
