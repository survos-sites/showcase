<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Component;
use App\Entity\Site;
use App\Enum\ComponentKind;
use App\Repository\ComponentRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Survos\CoreBundle\Service\SurvosUtils;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use function Symfony\Component\String\u;

final class AppService
{
    public function __construct(
        private readonly ComponentRepository $componentRepo,
        private readonly SiteRepository $siteRepo,
        private readonly EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {}

    /** @return array<string, array{path: string, kind: ComponentKind}> */
    private function getSources(): array
    {
        $base = dirname($this->projectDir);

        return [
            'bundles'   => ['path' => "$base/mono/bu",  'kind' => ComponentKind::Bundle],
            'libraries' => ['path' => "$base/mono/lib", 'kind' => ComponentKind::Library],
            'apps'      => ['path' => $base,             'kind' => ComponentKind::App],
        ];
    }

    #[AsCommand('app:load', 'load survos components from local sources')]
    public function load(SymfonyStyle $io): int
    {
        $runningSites = $this->getProxySites($io);

        foreach ($this->getSources() as $label => ['path' => $path, 'kind' => $kind]) {
            if (!is_dir($path)) {
                $io->warning("Source path not found: $path");
                continue;
            }

            $dirs = (new Finder())->in($path)->directories()->depth(0);
            $io->isVerbose() && $io->section(ucfirst($label) . " ($path)");

            foreach ($dirs as $dir) {
                $this->loadComponent($dir->getRealPath(), $kind, $runningSites, $io);
            }
        }

        $this->em->flush();

        $io->success(sprintf(
            'Components: %d   Sites: %d',
            $this->componentRepo->count([]),
            $this->siteRepo->count([]),
        ));

        return Command::SUCCESS;
    }

    #[AsCommand('app:deprecate', 'mark a component as deprecated (excluded from updates)')]
    public function deprecate(SymfonyStyle $io, #[Argument('composer name, e.g. survos-sites/old-app')] string $composerName): int
    {
        $id = str_replace('/', '__', $composerName);
        $component = $this->componentRepo->find($id);
        if (!$component) {
            $io->error("Component not found: $composerName");
            return Command::FAILURE;
        }
        $component->deprecated = true;
        $this->em->flush();
        $io->success("Marked as deprecated: $composerName");
        return Command::SUCCESS;
    }

    #[AsCommand('app:update', 'run composer/server updates on loaded components')]
    public function update(SymfonyStyle $io): int
    {
        foreach ($this->componentRepo->findAll() as $component) {
            if (!$component->localDir || !is_dir($component->localDir)) {
                continue;
            }
            $io->writeln($component->composerName);
        }

        return Command::SUCCESS;
    }

    /** @return array<string, int> siteCode => localPort */
    private function getProxySites(SymfonyStyle $io): array
    {
        try {
            $running = [];
            foreach (SurvosUtils::getSymfonyProxySites() as $site) {
                if (empty($site['port']) || !is_numeric($site['port']) || empty($site['domains'])) {
                    continue;
                }
                foreach ((array) $site['domains'] as $domain) {
                    $host = (string) u(parse_url($domain, PHP_URL_HOST) ?? '')->before('.wip');
                    if ($host) {
                        $running[$host] = (int) $site['port'];
                    }
                }
            }
            return $running;
        } catch (\Throwable) {
            $io->isVerbose() && $io->warning('Symfony proxy not running — localPort will not be set');
            return [];
        }
    }

    /** @param array<string, int> $runningSites */
    private function loadComponent(string $dir, ComponentKind $sourceKind, array $runningSites, SymfonyStyle $io): ?Component
    {
        $composerFile = "$dir/composer.json";
        if (!file_exists($composerFile)) {
            return null;
        }

        $composerData = json_decode(file_get_contents($composerFile), true);
        $composerName = $composerData['name'] ?? null;
        if (!$composerName) {
            return null;
        }

        // use composer type as the authority on kind
        $kind = ComponentKind::tryFrom($composerData['type'] ?? '') ?? $sourceKind;

        $code = str_replace('/', '__', $composerName);
        $component = $this->componentRepo->find($code) ?? new Component($composerName);
        if (!$this->em->contains($component)) {
            $this->em->persist($component);
        }

        $component->kind                = $kind;
        $component->name                = basename($dir);
        $component->localDir            = $dir;
        $component->composerJson        = $composerData;
        $component->description         = $composerData['description'] ?? null;
        $component->minimumStability    = $composerData['minimum-stability'] ?? null;

        foreach (['overview' => 'OVERVIEW.md', 'plan' => 'PLAN.md'] as $field => $file) {
            if (file_exists("$dir/$file")) {
                $component->$field = file_get_contents("$dir/$file");
            }
        }

        // App-kind only: read dokku remote from .git/config → create/update Site
        if ($kind === ComponentKind::App) {
            $this->loadSite($dir, $component, $runningSites);
        }

        $io->isVeryVerbose() && $io->writeln("  $composerName");

        return $component;
    }

    /** @param array<string, int> $runningSites */
    private function loadSite(string $dir, Component $component, array $runningSites): void
    {
        $gitConfig = "$dir/.git/config";
        if (!file_exists($gitConfig)) {
            return;
        }

        $ini = parse_ini_file($gitConfig, process_sections: true);
        $dokkuUrl = $ini['remote dokku']['url'] ?? null;
        if (!$dokkuUrl || !preg_match('/:([a-z0-9_-]+)$/i', $dokkuUrl, $m)) {
            return;
        }

        $siteCode = $m[1];
        $dokkuHost = preg_replace('/^.*@/', '', explode(':', $dokkuUrl)[0]);

        $site = $this->siteRepo->find($siteCode) ?? new Site($siteCode);
        if (!$this->em->contains($site)) {
            $this->em->persist($site);
        }

        $site->component  = $component;
        $site->dokkuHost  = $dokkuHost;
        $site->localPort  = $runningSites[$siteCode] ?? null;
    }
}
