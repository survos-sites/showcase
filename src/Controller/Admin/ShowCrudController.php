<?php

namespace App\Controller\Admin;

use ApiPlatform\Metadata\UrlGeneratorInterface;
use App\Entity\Inst;
use App\Entity\Show;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Survos\CoreBundle\Controller\BaseCrudController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Workflow\WorkflowInterface;

class ShowCrudController extends BaseCrudController
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Show::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('code')
            ->formatValue(function ($value, Show $entity) {
                return '<a href="' . $this->generateUrl('app_player', ['cineCode' => $entity->getCode()]) . '">' . $value . '</a>';
            })->onlyOnIndex();
        yield TextField::new('title');
        yield IntegerField::new('totalTime');
        yield IntegerField::new('markerCount');
    }
}
