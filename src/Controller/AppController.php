<?php

namespace App\Controller;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Bakame\HtmlTable\Parser;
use Survos\Bundle\MakerBundle\Service\GeneratorService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Zenstruck\Console\RunsCommands;
use Zenstruck\Console\RunsProcesses;
use function Symfony\Component\String\u;

class AppController extends AbstractController
{
    #[Route('/', name: 'app_homepage', methods: [Request::METHOD_GET])]
    public function index(ProjectRepository $projectRepository): Response
    {
        $projects = []; //
        $names = [];
        $sites = Parser::new()
            ->ignoreTableHeader()
            ->tableHeader(['dir', 'port', 'domains'])
            ->parseFile('http://127.0.0.1:7080');
        foreach ($sites as $idx => $site) {
            // check if it's running locally
            if (!is_numeric($site['port'])) {
                continue;
            }
            if (empty($site['domains'])) {
                continue;
            }

            $url = $site['domains'];
            $host = parse_url($url, PHP_URL_HOST);
            $host = u($host)->before('.wip')->toString();
            $names[] = $host;
            $projects[] = $projectRepository->findOneBy(['name' => $host]);
        }

        return $this->render('home.html.twig', [
                'projects' => $projectRepository->findBy(['name' => $names], ['name' => 'ASC']),
        ]);
    }

    #[Route('/show/{id}', name: 'project_show', methods: [Request::METHOD_GET])]
    #[Template('show.html.twig')]
    public function show(Project $project): Response|array
    {
        return [
            'project' => $project,
        ];
    }

    #[Route('/generate', name: 'app_generate', methods: [Request::METHOD_GET])]
    public function generate(GeneratorService $generatorService): Response
    {

        $ns = $generatorService->generateController($nsName = "ProjectController");
        $class = $ns->getClasses()[array_key_first($ns->getClasses())];
        dd($class, $ns->getClasses());
        $generatorService->addMethod($class, 'show');
//        dd($ns, $class, '<?php ' . $ns);
        return $this->render('controller.html.twig', [
            'ns' => $ns
        ]);
    }
}
