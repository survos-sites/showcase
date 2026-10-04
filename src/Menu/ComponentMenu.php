<?php

declare(strict_types=1);

namespace App\Menu;

use App\Entity\Component;
use App\Repository\ComponentRepository;
use Survos\TablerBundle\Event\MenuEvent;
use Survos\TablerBundle\Menu\MenuBuilderTrait;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * The second row for every page about one component. It is given nothing: `component` arrives as the controller's own
 * argument. PAGE_NAV is the same pages for every component; PAGE_ACTIONS moves between components (the same page for
 * the previous, next or any other one of the same kind) and leaves the site.
 */
final class ComponentMenu
{
    use MenuBuilderTrait;

    private const PAGES = [
        'component_show' => ['Overview', 'tabler:layout-dashboard'],
        'component_details' => ['Details', 'tabler:file-code'],
        'component_libraries' => ['Libraries', 'tabler:packages'],
        'component_screenshots' => ['Screenshots', 'tabler:photo'],
    ];

    public function __construct(
        private readonly RequestStack $requests,
        private readonly ComponentRepository $components,
        protected readonly ?RouterInterface $router = null,
    ) {}

    #[AsEventListener(event: MenuEvent::PAGE_NAV)]
    public function nav(MenuEvent $event): void
    {
        $component = $event->getOption('component');
        if (!$component instanceof Component) {
            return;
        }
        foreach (self::PAGES as $route => [$label, $icon]) {
            $this->add($event->getMenu(), $route, $component, label: $label, icon: $icon, translationDomain: false);
        }
    }

    #[AsEventListener(event: MenuEvent::PAGE_ACTIONS)]
    public function actions(MenuEvent $event): void
    {
        $component = $event->getOption('component');
        if (!$component instanceof Component) {
            return;
        }
        $menu = $event->getMenu();
        // The page you are on, for the neighbour: Libraries of this one becomes Libraries of the next.
        $route = (string) $this->requests->getMainRequest()?->attributes->get('_route');
        $route = isset(self::PAGES[$route]) ? $route : 'component_show';

        $siblings = $component->kind === null ? [] : $this->components->findBy(['kind' => $component->kind], ['name' => 'ASC']);
        $ids = array_map(static fn (Component $c): string => $c->id, $siblings);
        $at = array_search($component->id, $ids, true);
        if ($at !== false) {
            if (isset($siblings[$at - 1])) {
                $this->add($menu, $route, $siblings[$at - 1], label: '‹ '.$siblings[$at - 1]->name, translationDomain: false)->setLinkAttribute('title', 'Previous '.$component->kind->name);
            }
            if (isset($siblings[$at + 1])) {
                $this->add($menu, $route, $siblings[$at + 1], label: $siblings[$at + 1]->name.' ›', translationDomain: false)->setLinkAttribute('title', 'Next '.$component->kind->name);
            }
        }
        $this->add($menu, uri: $component->githubUrl, label: 'GitHub', icon: 'tabler:brand-github', external: true, translationDomain: false);
    }
}
