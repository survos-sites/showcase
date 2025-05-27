<?php

namespace App\Controller;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Repository\ShowRepository;
use Bakame\TabularData\HtmlTable\Parser;
use Survos\Bundle\MakerBundle\Service\GeneratorService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Zenstruck\Console\RunsCommands;
use Zenstruck\Console\RunsProcesses;
use function Symfony\Component\String\u;

class AppController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
        private readonly ProjectRepository                 $repo,
        #[Autowire('%kernel.environment%')] private string $environment,
    )
    {
    }

    #[Route('/', name: 'app_homepage', methods: [Request::METHOD_GET])]
    public function index(ProjectRepository $projectRepository,
                          ShowRepository $showRepository,
                          #[MapQueryParameter] bool $runningOnly = true): Response
    {
        $running = [];
        //
        $names = [];
        if ($this->environment === 'dev' && $runningOnly) {
            $sites = Parser::new()->ignoreTableHeader()->tableHeader(['dir', 'port', 'domains'])->parseFile('http://127.0.0.1:7080');
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
                $running[] = $projectRepository->findOneBy(['name' => $host]);
            }
            $running = $projectRepository->findBy(['name' => $names], ['name' => 'ASC']);
        }
        $projects = $projectRepository->findBy([], ['name' => 'ASC']);
        return $this->render('home.html.twig', [
            'shows' => $showRepository->findAll(),
            'casts' => (new Finder())->in($this->projectDir . '/public')->name('*.cast')->files(),
            'runningOnly' => $runningOnly,
            'running' => $running,
            'projects' => $projects]);
    }

    #[Route('/show/{id:project}', name: 'project_show', methods: [Request::METHOD_GET])]
    #[Template('show.html.twig')]
    public function show(Project $project): Response|array
    {
        return ['project' => $project];
    }

    #[Route('/generate', name: 'app_generate', methods: [Request::METHOD_GET])]
    public function generate(GeneratorService $generatorService): Response
    {
        $ns = $generatorService->generateController($nsName = "ProjectController");
        $class = $ns->getClasses()[array_key_first($ns->getClasses())];
        dd($class, $ns->getClasses());
        $generatorService->addMethod($class, 'show');
        //        dd($ns, $class, '<?php ' . $ns);
        return $this->render('controller.html.twig', ['ns' => $ns]);
    }

}
