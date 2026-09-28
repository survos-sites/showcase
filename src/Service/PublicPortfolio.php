<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Component;
use App\Enum\ComponentKind;
use App\Repository\ComponentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/** Explicit publication list: a deployed app or a public repository is not publication consent. */
final class PublicPortfolio
{
    private const PUBLIC_APPS = [
        'zm' => ['survos-sites/zm', 'Recordia', 'recordia.org', 'Discover and explore digital collections from museums and archives.'],
        'ink' => ['survos-sites/ink', 'Ink', 'inkstory.org', 'A reading room for historical newspapers and periodicals.'],
        'fotostory' => ['survos-sites/fotostory', 'FotoStory', 'fotostory.org', 'Explore community photo archives and the stories behind their images.'],
    ];

    public function __construct(
        private readonly ComponentRepository $components,
        private readonly EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {}

    public function sites(): array
    {
        $sites = [];
        foreach ($this->snapshot()['sites'] as $site) {
            if ($this->components->find(str_replace('/', '__', $site['repository'])) !== null) {
                $sites[] = $site;
            }
        }
        return $sites;
    }

    /** Run from an operator workstation; no production SSH/GitHub credentials are required. */
    #[AsCommand('portfolio:sync', 'refresh the public portfolio snapshot from Dokku and GitHub')]
    public function sync(SymfonyStyle $io): int
    {
        $report = new Process(['ssh', '-o', 'BatchMode=yes', 'fsn1', 'domains:report']);
        $report->mustRun();
        $domains = [];
        $app = null;
        foreach (explode("\n", $report->getOutput()) as $line) {
            if (preg_match('/^=====> (\S+) domains information/', $line, $matches)) {
                $app = $matches[1];
            } elseif ($app !== null && preg_match('/Domains app vhosts:\s+(.+)/', $line, $matches)) {
                $domains[$app] = preg_split('/\s+/', trim($matches[1]));
            }
        }
        $sites = [];
        foreach (self::PUBLIC_APPS as $app => [$repository, $title, $domain, $description]) {
            if (!in_array($domain, $domains[$app] ?? [], true)) {
                throw new \RuntimeException(sprintf('Expected public domain %s is not assigned to %s in Dokku.', $domain, $app));
            }
            $status = new Process(['ssh', '-o', 'BatchMode=yes', 'fsn1', 'ps:report', $app, '--running']);
            $status->mustRun();
            if (trim($status->getOutput()) !== 'true') {
                throw new \RuntimeException(sprintf('Dokku app %s is not running.', $app));
            }
            $github = new Process(['gh', 'repo', 'view', $repository, '--json', 'description,url,isPrivate']);
            $github->mustRun();
            $metadata = json_decode($github->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            $sites[] = [
                'app' => $app, 'repository' => $repository, 'title' => $title,
                'url' => 'https://'.$domain, 'description' => $description,
                'githubUrl' => $metadata['isPrivate'] ? null : $metadata['url'],
                'repositoryDescription' => $metadata['isPrivate'] ? null : $metadata['description'],
            ];
        }
        (new Filesystem())->dumpFile($this->projectDir.'/config/public-portfolio.json', json_encode([
            'generatedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'sources' => ['Dokku fsn1 domains and process state', 'GitHub repository metadata'],
            'sites' => $sites,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
        $io->success('Refreshed public portfolio from Dokku and GitHub. Review and commit the snapshot.');
        return Command::SUCCESS;
    }

    #[AsCommand('portfolio:load', 'load the reviewed public portfolio snapshot')]
    public function load(SymfonyStyle $io): int
    {
        foreach ($this->snapshot()['sites'] as $site) {
            $name = $site['repository'];
            $component = $this->components->find(str_replace('/', '__', $name)) ?? new Component($name);
            $component->kind = ComponentKind::App;
            $component->name = $site['title'];
            $component->description = $site['description'];
            $this->em->persist($component);
        }
        $this->em->flush();
        $io->success('Public portfolio loaded.');
        return Command::SUCCESS;
    }

    private function snapshot(): array
    {
        return json_decode(file_get_contents($this->projectDir.'/config/public-portfolio.json'), true, flags: JSON_THROW_ON_ERROR);
    }
}
