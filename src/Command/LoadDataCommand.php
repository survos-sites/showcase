<?php

namespace App\Command;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Castor\Attribute\AsSymfonyTask;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Yaml\Yaml;

#[AsCommand('app:load', 'load projects from local source')]
#[AsSymfonyTask('app:load')]
final class LoadDataCommand
{

    public function __construct(
        private ProjectRepository      $projectRepository,
        private EntityManagerInterface $entityManager
    )
    {
    }

    public function __invoke(
        SymfonyStyle                          $io,
        #[Argument('The root directory for the projects', name: 'root')]
        string                                $rootDirectory = './..',
        #[Option] ?bool                    $force = null,
        #[Option("do composer update")] ?bool $update=null
    ): int
    {
        $finder = new Finder();
        foreach ($finder->in($rootDirectory)->directories()->depth(0) as $file) {
            $dir = $file->getRealPath();
            $appJson = $dir . '/app.json';
            if (file_exists($appJson)) {
                $io->info("Loading " . $dir);
                $project = $this->loadProject($dir, $io);
                $projects[$project->getId()] = $project;
            }
        }
        $io->success("Projects: " . $this->projectRepository->count([]));
        if ($update) {
            foreach ($this->projectRepository->findBy([], ['lastUpdatedTime' => 'DESC']) as $project) {
                dump($project->getLocalDir());
                $this->updateGit($project);
            }
        }
        return Command::SUCCESS;
    }

    private function updateGit(Project $project): void
    {
        if ($project->getMinimumStability() === 'dev') {
            return;
        }
        $dir = $project->getLocalDir();
        // @todo: skip the ones that are already running
        $processes = [
            new Process(['composer', 'config', 'minimum-stability', 'stable', "--working-dir=$dir"]),
            new Process(['composer', 'config', 'extra.symfony.require', '^7.3', "--working-dir=$dir"]),
//            new Process(['composer', 'update', "--working-dir=$dir"]),
            new Process(['symfony', 'server:start', "-d",  "--dir=$dir"]),
            // composer req phpunit/phpunit:^12.1 --dev phpunit/php-code-coverage:^12.1 -W
        ];
        foreach ($processes as $process) {
            $process->setTimeout(600);
            dump($process->getCommandLine());
            $process->run(function ($type, $buffer): void {
                if (Process::ERR === $type) {
                    echo 'ERR > ' . $buffer;
                } else {
                    echo 'OUT > ' . $buffer;
                }
            });
            $list = $process->getOutput();
            dump($list);
            $project->setLastUpdatedTime(new \DateTimeImmutable());
//        $process->start();
//
//        while ($process->isRunning()) {
//            // waiting for process to finish
//        }
//
//        echo $process->getOutput();

        }

//        $process = new Process(['composer', 'update', "--working-dir=$dir"]);
//        $process->setTimeout(600);
//        dump($process->getCommandLine());

//        $process->start();
//
//        while ($process->isRunning()) {
//            // waiting for process to finish
//        }
//
//        echo $process->getOutput();

        return;

    }

    private function loadProject($dir, SymfonyStyle $io): Project
    {
        foreach (['app.json', 'composer.json', 'config/packages/pwa.yaml', '.git/config'] as $file) {
            $fullFile = $dir . '/' . $file;
            if (!file_exists($fullFile)) {
                continue;
            }
            switch ($file) {
                case  '.git/config':
                    $process = new Process(['git', 'config', "-f", "$fullFile", '--list']);
                    dump($process->getCommandLine());
                    $process->run();


// executes after the command finishes
                    if (!$process->isSuccessful()) {
                        dd($process->getErrorOutput(), $process->getOutput());
                    }
                    $list = $process->getOutput();
                    $listData = parse_ini_string($list);
                    $gitUrl = $listData['remote.origin.url'];


                    $gitConfig = file_get_contents($fullFile);
                    //
                    break;
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
                    $project->setLocalDir($dir);
                    $project->setStatus(null);
                    $project->setAppJson($app);
                    break;
                case 'composer.json':
                    $composerData = json_decode(file_get_contents($fullFile), true);
                    foreach (['name', 'description',
//                                 'keywords'
                             ] as $requiredKey) {
                        if (!array_key_exists($requiredKey, $composerData)) {
                            $status = "skipping, missing composer.$requiredKey in $project ($fullFile)";
                            $project->setStatus($status);
                            $io->warning($status);
                        }
                    }
                    $minStability = $composerData['minimum-stability']??null;
                    $project->setMinimumStability($minStability);
//                    dd($composerData['minimum-stability'], $composerData['require']['php']);
                    $project->setComposerJson($composerData);
                    break;
                case 'config/packages/pwa.yaml':
                    $pwa = Yaml::parseFile($fullFile);
                    if ($pwa['pwa'] ?? false) {
                        $project->setPwaYaml($pwa['pwa']);
                    } else {
                        $io->warning("Unable to load path: $fullFile");
                    }
                    break;
            }
        }
        $this->entityManager->flush();
        return $project;
    }
}
