<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Component;
use App\Repository\ComponentRepository;
use App\Repository\SiteRepository;
use Survos\FieldBundle\Attribute\RouteMeta;
use Survos\FieldBundle\Enum\Audience;
use Survos\FieldBundle\Enum\Purpose;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * One component (app, bundle or library) and the same pages for every one of them. The Component argument is all a page
 * hands to the menus: ComponentMenu reads it for the tabs, the switcher and the actions, and the breadcrumb is built from
 * the Show route below, so none of that is passed around by hand.
 */
#[Route('/component/{componentId}')]
class ComponentController extends AbstractController
{
    #[Route('/show', name: 'component_show', methods: [Request::METHOD_GET])]
    #[RouteMeta(description: 'Overview of one component: what it is, its status and where it lives.', entity: Component::class, purpose: Purpose::Show, label: 'Overview', parents: ['app_catalog'], audience: Audience::Authenticated)]
    #[Template('component/overview.html.twig')]
    public function show(Component $component, SiteRepository $sites): Response|array
    {
        return ['component' => $component, 'site' => $sites->findOneBy(['component' => $component])];
    }

    #[Route('/details', name: 'component_details', methods: [Request::METHOD_GET])]
    #[RouteMeta(description: 'The raw composer.json and PWA manifest the overview is built from.', entity: Component::class, label: 'Details')]
    #[Template('component/details.html.twig')]
    public function details(Component $component): Response|array
    {
        return ['component' => $component];
    }

    #[Route('/libraries', name: 'component_libraries', methods: [Request::METHOD_GET])]
    #[RouteMeta(description: 'The packages this component requires, linked to their own pages when we track them.', entity: Component::class, label: 'Libraries')]
    #[Template('component/libraries.html.twig')]
    public function libraries(Component $component, ComponentRepository $components): Response|array
    {
        $ids = array_map(static fn (string $name): string => str_replace('/', '__', $name), $component->dependencies);
        $known = [];
        foreach ($components->findBy(['id' => $ids]) as $dependency) {
            $known[$dependency->composerName] = $dependency;
        }

        return ['component' => $component, 'known' => $known];
    }

    #[Route('/screenshots', name: 'component_screenshots', methods: [Request::METHOD_GET])]
    #[RouteMeta(description: 'Screenshots of the deployed or local app.', entity: Component::class, label: 'Screenshots')]
    #[Template('component/screenshots.html.twig')]
    public function screenshots(Component $component, SiteRepository $sites): Response|array
    {
        $site = $sites->findOneBy(['component' => $component]);
        $path = $site?->screenshotPath;

        return ['component' => $component, 'site' => $site, 'screenshot' => $path ? '/'.basename($path) : null];
    }
}
