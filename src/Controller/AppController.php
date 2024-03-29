<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use Survos\Bundle\MakerBundle\Service\GeneratorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Zenstruck\Console\RunsCommands;
use Zenstruck\Console\RunsProcesses;

class AppController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(ProjectRepository $projectRepository): Response
    {
        return $this->render('home.html.twig', [
            'projects' => $projectRepository->findAll()
        ]);
    }

    #[Route('/generate', name: 'app_generate')]
    public function generate(GeneratorService $generatorService): Response
    {
        $ns = $generatorService->generateController($nsName = "TestSomething");
        $class = $ns->getClasses()[array_key_first($ns->getClasses())];
        $generatorService->addMethod($class, 'mymethod');
//        dd($ns, $class, '<?php ' . $ns);
        return $this->render('controller.html.twig', [
            'ns' => $ns
        ]);
    }
}
