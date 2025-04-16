<?php

namespace App\Command;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
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

    private function updateGit($dir)
    {
        $process = new Process(['composer', 'update', "--working-dir=$dir"]);
        $process->setTimeout(600);
        dump($process->getCommandLine());

//        $process->start();
//
//        while ($process->isRunning()) {
//            // waiting for process to finish
//        }
//
//        echo $process->getOutput();

        $process->run(function ($type, $buffer): void {
            if (Process::ERR === $type) {
                echo 'ERR > ' . $buffer;
            } else {
                echo 'OUT > ' . $buffer;
            }
        });
        $list = $process->getOutput();
        dump($list);
        return;

    }

    private function loadProject($dir): void
    {
        foreach (['app.json', 'composer.json', 'config/packages/pwa.yaml', '.git/config'] as $file) {
            $fullFile = $dir . '/' . $file;
            if (!file_exists($fullFile)) {
                continue;
            }
            switch ($file) {
                case  '.git/config':
                    $process = new Process(['git', 'config', "-f" , "$fullFile", '--list']);
                    dump($process->getCommandLine());
                    $process->run();


// executes after the command finishes
                    if (!$process->isSuccessful()) {
                        dd($process->getErrorOutput(), $process->getOutput());
                    }
                    $list  = $process->getOutput();
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
                            $this->io()->warning($status);
                        }
                    }
                    $project->setComposerJson($composerData);
                    break;
                case 'config/packages/pwa.yaml':
                    $pwa = Yaml::parseFile($fullFile);
                    if ($pwa['pwa']?? false) {
                        $project->setPwaYaml($pwa['pwa']);
                    } else {
                        $this->io()->warning("Unable to load path: $fullFile");
                    }
                    break;
            }
        }
        $this->entityManager->flush();
    }
}
