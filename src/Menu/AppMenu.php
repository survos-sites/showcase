<?php

namespace App\Menu;

use App\Controller\Admin\MeiliDashboardController;
use App\Entity\Ciine;
use App\Entity\Project;
use App\Entity\Show;
use Survos\MeiliBundle\Service\MeiliService;
use Survos\TablerBundle\Event\MenuEvent;
use Survos\TablerBundle\Menu\MenuBuilderTrait;
use Survos\TablerBundle\Service\MenuService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class AppMenu // @todo: trait
{
    use MenuBuilderTrait;

    public function __construct(
        #[Autowire('%kernel.environment%')] protected string $env,
        private MenuService                                  $menuService,
        private Security                                     $security,
        private readonly MeiliService $meiliService,
        private ?AuthorizationCheckerInterface               $authorizationChecker = null
    ) {
    }

    public function appAuthMenu(MenuEvent $event): void
    {
        $menu = $event->getMenu();
        $this->menuService->addAuthMenu($menu);
    }

    #[AsEventListener(event: MenuEvent::NAVBAR_MENU)]
    public function navbarMenu(MenuEvent $event): void
    {
        $menu = $event->getMenu();
        $options = $event->options;
        $this->add($menu, 'app_homepage');
        $this->add($menu, 'app_slides', label: 'slides');
        $this->add($menu, 'survos_commands');
        $this->add($menu, MeiliDashboardController::MEILI_ROUTE, label: 'ez');

//        $this->add($menu, uri: '/db.svg', external: true, label: 'db.svg');

        // easyadmin should provide us what we need, a simple filter
        if (0) {
            $subMenu = $this->addSubmenu($menu, 'meili');
            $this->add($subMenu, 'media_meili');
            $this->add($subMenu, 'media_index');
        }

        foreach ($this->meiliService->settings as $index => $setting) {
            $this->add($menu, 'meili_insta', ['indexName' => $index], label: $index, external: true);

        }


        if ($this->env === 'dev') {

            $subMenu = $this->addSubmenu($menu, 'workflows');
            $this->add($subMenu, 'survos_workflows');

            $subMenu = $this->addSubmenu($menu, 'survos_commands');
            $this->add($subMenu, 'survos_commands', label: 'All');
            foreach (['state:iterate', 'storage:iterate'] as $commandName) {
                $this->add($subMenu, 'survos_command', ['commandName' => $commandName], $commandName);
            }
            $subMenu = $this->addSubmenu($menu, 'state:iterate');
            foreach ([Show::class, Project::class, Ciine::class] as $className) {
                $className = str_replace("\\", "\\\\", $className);
                $this->add($subMenu, 'survos_command', ['commandName' => 'state:iterate', 'className' => $className], $className);
            }
            $this->add($subMenu, 'survos_workflows', label: 'Workflows');

        }

        //        $this->add($menu, 'app_homepage');
        // for nested menus, don't add a route, just a label, then use it for the argument to addMenuItem

        $nestedMenu = $this->addSubmenu($menu, 'git', icon: 'tabler:brand-github');

        foreach ([''=>'repo', '/issues'=>'issues'] as $path => $label) {
            $this->add($nestedMenu, uri: 'https://github.com/survos-sites/showcase' . $path, label: $label);

        }
    }
}
