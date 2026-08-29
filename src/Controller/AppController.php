<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\ComponentKind;
use App\Repository\ComponentRepository;
use App\Service\SymfonyProxy;
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
        // Populated on every dev page load, not only behind ?runningOnly=1 -- "which of
        // my repos are running right now" is the question the homepage exists to answer,
        // and an opt-in flag nobody remembers means it always rendered empty. This reads
        // the proxy live rather than Site::$localPort, which is only a snapshot of the
        // last app:load and goes stale the moment a server starts or stops.
        $running = [];
        if ($this->environment === 'dev') {
            $codes = array_keys(SymfonyProxy::getRunningCodes());
            $running = $codes
                ? $componentRepository->findBy(['name' => $codes], ['name' => 'ASC'])
                : [];
        }

        return $this->render('home.html.twig', [
            'runningOnly' => $runningOnly,
            'running'    => $running,
            'components' => $componentRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

}
