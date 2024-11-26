<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue('AUTO')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?array $appJson = null;

    #[ORM\Column(nullable: true)]
    private ?array $composerJson = null;

    #[ORM\Column(nullable: true)]
    private ?array $pwaYaml = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppJson(): ?array
    {
        return $this->appJson;
    }

    public function setAppJson(?array $appJson): static
    {
        $this->appJson = $appJson;

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

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

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
                    'display' => str_replace('-bundle', '-b', $name),
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
}
