<?php

namespace App\Controller\Admin;

use App\Entity\Project;
use App\Workflow\IProjectWorkflow;
use App\Workflow\ProjectWorkflow;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Workflow\WorkflowInterface;

class ProjectCrudController extends AbstractCrudController
{
    public function __construct(
        #[Target(IProjectWorkflow::WORKFLOW_NAME)] private WorkflowInterface $workflow
    )
    {
    }

    public static function getEntityFqcn(): string
    {
        return Project::class;
    }

    public function configureFields(string $pageName): iterable
    {

        yield IdField::new('id')->hideOnForm();
//        yield AvatarField::new('avatarUrl')
//            ->setHeight($pageName === Crud::PAGE_DETAIL ? 96 : 48);

        /** @var Field $field */
        foreach (parent::configureFields($pageName) as $field) {
            $propertyName = $field->getAsDto()->getPropertyNameWithSuffix();
            $easyadminField = match ($propertyName) {
                'marking' => ChoiceField::new('marking')->setChoices(
                    $this->workflow->getDefinition()->getPlaces()
                ),
                'id' => null,
//                'downloadStatusCode' => $field->setLabel('Download Status'),

                default => $field,
            };
            if ($easyadminField) {
                yield $easyadminField;
            }
        }
    }

    public function configureFilters(Filters $filters): Filters
    {
        $places = $this->workflow->getDefinition()->getPlaces();
        return $filters
            ->add(ChoiceFilter::new('marking')
                ->setChoices($places)
            )
            ;
//            ->add('name')
//            ->add(BooleanFilter::new('owned'));
    }
}
