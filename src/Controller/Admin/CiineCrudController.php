<?php

namespace App\Controller\Admin;

use App\Entity\Ciine;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Survos\EzBundle\Controller\BaseCrudController;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Bytes;

class CiineCrudController extends BaseCrudController
{
    public static function getEntityFqcn(): string
    {
        return Ciine::class;
    }

    #[AdminRoute('/app/ciine/{ciineId}', name: 'show')]
    #[Template('app/ciine/show.html.twig')]
    public function showCiine(
        #[MapEntity(id: 'ciineId')] Ciine $ciine): Response|array
    {
        return [
            'ciine' => $ciine,
        ];
    }



    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID');
//        yield TextField::new('title');
        yield IntegerField::new('filesize');
        yield IntegerField::new('duration');
        // ez_meili_ciine_show

        yield TextField::new('title')
            ->formatValue(function ($value, Ciine $entity) {
                return sprintf(
                    '<a href="%s">%s</a>',
                    $this->generateUrl('ez_meili_ciine_show', ['ciineId' => $entity->id]),
                    $value
                );
            });

    }
}
