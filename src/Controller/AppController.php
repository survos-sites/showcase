<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ComponentRepository;
use App\Repository\ShowRepository;
use Survos\CoreBundle\Service\SurvosUtils;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class AppController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
        private readonly ComponentRepository $repo,
        private readonly ShowRepository $showRepo,
        #[Autowire('%kernel.environment%')] private string $environment,
    ) {
    }

    #[Route('/blank', name: 'app_blank', methods: [Request::METHOD_GET])]
    #[Template('blank.html.twig')]
    public function blank(): Response|array
    {
        return [];
    }

    #[Route('/slides', name: 'app_slides', methods: [Request::METHOD_GET])]
    #[Template('app/slideshow.html.twig')]
    public function slideshow(Request $request): Response|array
    {
        return ['shows' => $this->showRepo->findBy([], limit: 30)];
    }

    #[Route('/', name: 'app_homepage', methods: [Request::METHOD_GET])]
    public function index(
        ComponentRepository $componentRepository,
        ShowRepository $showRepository,
        #[MapQueryParameter] bool $runningOnly = false,
    ): Response {
        $running = [];
        if ($this->environment === 'dev' && $runningOnly) {
            $sites = SurvosUtils::getSymfonyProxySites();
            $names = [];
            foreach ($sites as $site) {
                if (!is_numeric($site['port']) || empty($site['port']) || empty($site['domains'])) {
                    continue;
                }
                $host = parse_url($site['domains'][0] ?? $site['domains'], PHP_URL_HOST);
                $host = (string) \Symfony\Component\String\u($host)->before('.wip');
                $names[] = $host;
            }
            $running = $componentRepository->findBy(['name' => $names], ['name' => 'ASC']);
        }

        return $this->render('home.html.twig', [
            'shows'      => $showRepository->findAll(),
            'casts'      => (new Finder())->in($this->projectDir . '/public')->name('*.cast')->files(),
            'runningOnly' => $runningOnly,
            'running'    => $running,
            'components' => $componentRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

}
