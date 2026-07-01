<?php

namespace App\Menu;

use App\Entity\Ciine;
use App\Entity\Component;
use App\Entity\Show;
use Survos\SearchBundle\Registry\UxSearchRegistry;
use Survos\TablerBundle\Event\MenuEvent;
use Survos\TablerBundle\Menu\MenuBuilderTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class AppMenu // @todo: trait
{
    use MenuBuilderTrait;

    public function __construct(
        #[Autowire('%kernel.environment%')] protected string $env,
        private Security                                     $security,
        private readonly UxSearchRegistry $searchRegistry,
        private readonly RouterInterface $router,
        private ?AuthorizationCheckerInterface               $authorizationChecker = null
    ) {
    }

    public function appAuthMenu(MenuEvent $event): void
    {
        $menu = $event->getMenu();

        if ($this->security->getUser()) {
            $this->add($menu, 'app_logout', label: 'Logout', icon: 'logout');

            return;
        }

        $this->add($menu, 'app_login', label: 'Login', icon: 'login');
        $this->add($menu, 'app_register', label: 'Register', icon: 'user-plus');
    }

    #[AsEventListener(event: MenuEvent::NAVBAR_MENU)]
    public function navbarMenu(MenuEvent $event): void
    {
        $menu = $event->getMenu();
        $options = $event->options;
        $this->add($menu, 'app_homepage', label: 'All');
        $this->add($menu, 'app_apps', label: 'Apps');
        $this->add($menu, 'app_tools', label: 'Tools');
        $this->add($menu, 'app_slides', label: 'slides');
        if ($this->hasRoute('survos_commands')) {
            $this->add($menu, 'survos_commands');
        }

//        $this->add($menu, uri: '/db.svg', external: true, label: 'db.svg');

        // Doctrine-native full-text search (survos/search-bundle), one link per searchable entity.
        $searches = $this->searchRegistry->all();
        if ($searches !== []) {
            $searchMenu = $this->addSubmenu($menu, 'Search');
            foreach ($searches as $descriptor) {
                $this->add($searchMenu, 'survos_entity_ux_search', $descriptor);
            }
        }


        if ($this->env === "dev") {

            $subMenu = $this->addSubmenu($menu, "workflows");
            $this->add($subMenu, "survos_workflows");

            if ($this->hasRoute("survos_commands") && $this->hasRoute("survos_command")) {
                $subMenu = $this->addSubmenu($menu, "survos_commands");
                $this->add($subMenu, "survos_commands", label: "All");
                foreach (["state:iterate", "storage:iterate"] as $commandName) {
                    $this->add($subMenu, "survos_command", ["commandName" => $commandName], $commandName);
                }

                $subMenu = $this->addSubmenu($menu, "state:iterate");
                foreach ([Show::class, Component::class, Ciine::class] as $className) {
                    $className = str_replace("\\", "\\\\", $className);
                    $this->add($subMenu, "survos_command", ["commandName" => "state:iterate", "className" => $className], $className);
                }
                $this->add($subMenu, "survos_workflows", label: "Workflows");
            }

        }

        //        $this->add($menu, 'app_homepage');
        // for nested menus, don't add a route, just a label, then use it for the argument to addMenuItem

        $nestedMenu = $this->addSubmenu($menu, 'git', icon: 'tabler:brand-github');

        foreach ([''=>'repo', '/issues'=>'issues'] as $path => $label) {
            $this->add($nestedMenu, uri: 'https://github.com/survos-sites/showcase' . $path, label: $label);

        }
    }

    private function hasRoute(string $route): bool
    {
        return null !== $this->router->getRouteCollection()->get($route);
    }
}
