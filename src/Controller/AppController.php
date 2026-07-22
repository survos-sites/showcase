<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\ComponentKind;
use App\Repository\ComponentRepository;
use Survos\CoreBundle\Service\SurvosUtils;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class AppController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
        private readonly ComponentRepository $repo,
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
        return [];
    }

    #[Route('/opan', name: 'app_opan', methods: [Request::METHOD_GET])]
    #[Template('app/opan.html.twig')]
    public function opan(Request $request): Response|array
    {
        return [];
    }

    #[Route('/apps', name: 'app_apps', methods: [Request::METHOD_GET])]
    public function apps(ComponentRepository $componentRepository): Response
    {
        return $this->render('home.html.twig', [
            'title'      => 'Apps',
            'components' => $componentRepository->findBy(
                ['kind' => ComponentKind::App],
                ['minimumStability' => 'ASC', 'name' => 'ASC']
            ),
            'running'    => [],
        ]);
    }

    #[Route('/tools', name: 'app_tools', methods: [Request::METHOD_GET])]
    public function tools(ComponentRepository $componentRepository): Response
    {
        return $this->render('home.html.twig', [
            'title'      => 'Tools',
            'components' => $componentRepository->findBy(
                ['kind' => [ComponentKind::Bundle, ComponentKind::Library]],
                ['name' => 'ASC']
            ),
            'running'    => [],
        ]);
    }

    #[Route('/', name: 'app_homepage', methods: [Request::METHOD_GET])]
    public function index(
        ComponentRepository $componentRepository,
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
            'runningOnly' => $runningOnly,
            'running'    => $running,
            'components' => $componentRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

}
