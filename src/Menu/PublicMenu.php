<?php

declare(strict_types=1);

namespace App\Menu;

use Survos\TablerBundle\Event\MenuEvent;
use Survos\TablerBundle\Menu\MenuBuilderTrait;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/** What a visitor to the public site sees; the internal catalogue links stay in AppMenu for admins and dev. */
final class PublicMenu
{
    use MenuBuilderTrait;

    #[AsEventListener(event: MenuEvent::NAVBAR_MENU, priority: 100)]
    public function navbar(MenuEvent $event): void
    {
        $menu = $event->getMenu();
        $this->add($menu, uri: '/#work', label: 'Our work', icon: 'tabler:building-community', translationDomain: false);
        $this->add($menu, uri: 'https://github.com/survos', label: 'GitHub', icon: 'tabler:brand-github', external: true, translationDomain: false);
        $this->add($menu, uri: 'https://medium.com/@tacman1123', label: 'Writing', icon: 'tabler:writing', external: true, translationDomain: false);
    }
}
