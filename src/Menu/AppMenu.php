<?php

namespace App\Menu;

use App\Entity\Project;
use App\Entity\Show;
use Survos\BootstrapBundle\Event\KnpMenuEvent;
use Survos\BootstrapBundle\Service\MenuService;
use Survos\BootstrapBundle\Traits\KnpMenuHelperInterface;
use Survos\BootstrapBundle\Traits\KnpMenuHelperTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class AppMenu implements KnpMenuHelperInterface
{
    use KnpMenuHelperTrait;

    public function __construct(
        #[Autowire('%kernel.environment%')] protected string $env,
        private MenuService $menuService,
        private Security $security,
        private ?AuthorizationCheckerInterface $authorizationChecker = null
    ) {
    }

    public function appAuthMenu(KnpMenuEvent $event): void
    {
        $menu = $event->getMenu();
        $this->menuService->addAuthMenu($menu);
    }

    #[AsEventListener(event: KnpMenuEvent::NAVBAR_MENU)]
    public function navbarMenu(KnpMenuEvent $event): void
    {
        $menu = $event->getMenu();
        $options = $event->getOptions();
        $this->add($menu, 'app_homepage');
        $this->add($menu, 'survos_commands');
//        $this->add($menu, 'admin', label: 'ez');

//        $this->add($menu, uri: '/db.svg', external: true, label: 'db.svg');

        // easyadmin should provide us what we need, a simple filter
        if (0) {
            $subMenu = $this->addSubmenu($menu, 'meili');
            $this->add($subMenu, 'media_meili');
            $this->add($subMenu, 'media_index');
        }


        if ($this->isEnv('dev')) {

            $subMenu = $this->addSubmenu($menu, 'workflows');
            $this->add($subMenu, 'survos_workflows');

            $subMenu = $this->addSubmenu($menu, 'survos_commands');
            $this->add($subMenu, 'survos_commands', label: 'All');
            foreach (['workflow:iterate', 'storage:iterate'] as $commandName) {
                $this->add($subMenu, 'survos_command', ['commandName' => $commandName], $commandName);
            }
            $subMenu = $this->addSubmenu($menu, 'workflow:iterate');
            foreach ([Show::class, Project::class] as $className) {
                $className = str_replace("\\", "\\\\", $className);
                $this->add($subMenu, 'survos_command', ['commandName' => 'workflow:iterate', 'className' => $className], $className);
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
