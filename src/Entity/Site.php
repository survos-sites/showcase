<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SiteRepository;
use Doctrine\ORM\Mapping as ORM;
use Survos\FieldBundle\Attribute\EntityMeta;
use Survos\FieldBundle\Attribute\Field;
use Survos\FieldBundle\Attribute\RouteIdentity;
use Survos\FieldBundle\Entity\RouteIdentityTrait;
use Survos\CoreBundle\Entity\RouteParametersInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: SiteRepository::class)]
#[EntityMeta(icon: 'tabler:world', group: 'Components', description: 'Deployed instances of App-kind components')]
#[RouteIdentity(field: 'id')]
final class Site implements \Stringable, RouteParametersInterface
{
    use RouteIdentityTrait;
    // PK: dokku app name parsed from remote.dokku.url in .git/config, e.g. "lingua"
    // from: dokku@dokku.survos.com:lingua → "lingua"
    #[ORM\Id]
    #[ORM\Column(length: 128)]
    #[Groups(['site.read'])]
    #[Field(searchable: true, sortable: true, order: 1)]
    public readonly string $id;

    #[ORM\ManyToOne(inversedBy: 'sites')]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true)]
    public ?Component $component = null;

    // dokku server hostname, e.g. "dokku.survos.com"
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['site.read'])]
    public ?string $dokkuHost = null;

    // port from Symfony proxy (:7080) — transient, updated on each app:load run
    #[ORM\Column(nullable: true)]
    #[Groups(['site.read'])]
    public ?int $localPort = null;

    // path to the last screenshot, e.g. public/lingua.wip.png
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $screenshotPath = null;

    // ── Computed / virtual ────────────────────────────────────────────────────

    #[Groups(['site.read'])]
    public string $productionUrl {
        get => sprintf('https://%s.survos.com', $this->id);
    }

    #[Groups(['site.read'])]
    public string $localUrl {
        get => sprintf('https://%s.wip', $this->id);
    }

    // true when Symfony proxy reported this site running on last app:load
    #[Groups(['site.read'])]
    public bool $isRunning {
        get => $this->localPort !== null;
    }

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
