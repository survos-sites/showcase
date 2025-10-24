<?php

namespace App\Controller\Admin;

use App\Entity\Ciine;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Survos\CoreBundle\Controller\BaseCrudController;
use Zenstruck\Bytes;

class CiineCrudController extends BaseCrudController
{
    public static function getEntityFqcn(): string
    {
        return Ciine::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID');
        yield TextField::new('title');
        yield IntegerField::new('filesize');
        yield IntegerField::new('duration');
    }
}
