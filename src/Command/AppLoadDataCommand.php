<?php

namespace App\Command;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Finder\Finder;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Yaml\Yaml;
use Zenstruck\Console\Attribute\Argument;
use Zenstruck\Console\ConfigureWithAttributes;
use Zenstruck\Console\InvokableServiceCommand;
use Zenstruck\Console\IO;
use Zenstruck\Console\RunsCommands;
use Zenstruck\Console\RunsProcesses;

#[AsCommand('app:load-data', 'load projects from local source')]
final class AppLoadDataCommand extends InvokableServiceCommand
{
    use RunsCommands;
    use RunsProcesses;

    public function __construct(private ProjectRepository $projectRepository,
        private EntityManagerInterface $entityManager
    )
    {
        parent::__construct();

    }

    public function __invoke(
        IO     $io,
        #[Argument('root', description: 'The root directory for the projects')]
        string $rootDirectory = './..',
    ): void
    {
        $finder = new Finder();
        foreach ($finder->in($rootDirectory)->directories()->depth(0) as $file) {
            $dir = $file->getRealPath();
            $appJson = $dir . '/app.json';
            if (file_exists($appJson)) {
                $io->info("Loading " . $dir);
                $this->loadProject($dir);
            }
        }
        $io->success("Projects: " . $this->projectRepository->count([]));
    }

    private function loadProject($dir)
    {
        foreach (['app.json', 'composer.json', 'config/packages/pwa.yaml'] as $file) {
            $fullFile = $dir . '/' . $file;
            if (!file_exists($fullFile)) {
                continue;
            }
            switch ($file) {
                case 'app.json':
                    $app = json_decode(file_get_contents($fullFile), true);
                    $name = $app['name'];
                    if (!$project = $this->projectRepository->findOneBy([
                        'name' => $name
                    ])) {
                        $project = (new Project())
                            ->setName($name);
                        $this->entityManager->persist($project);
                    }
                    $project->setStatus(null);
                    $project->setAppJson($app);
                    break;
                case 'composer.json':
                    $composerData = json_decode(file_get_contents($fullFile), true);
                    foreach (['name', 'description', 'keywords'] as $requiredKey) {
                        if (!array_key_exists($requiredKey, $composerData)) {
                            $status = "skipping, missing composer.$requiredKey in $project ($fullFile)";
                            $project->setStatus($status);
                            $this->io()->warning($status);
                        }
                    }
                    $project->setComposerJson($composerData);
                    break;
                case 'config/packages/pwa.yaml':
                    $pwa = Yaml::parseFile($fullFile);
                    $project->setPwaYaml($pwa['pwa']);
                    break;
            }
        }
        $this->entityManager->flush();
    }
}
