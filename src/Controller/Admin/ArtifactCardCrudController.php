<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\CardTypes\ArtifactCard;
use App\Enum\Type;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ArtifactCardCrudController extends AbstractCardCrudController
{
    public static function getEntityFqcn(): string
    {
        return ArtifactCard::class;
    }

    protected function getType(): Type
    {
        return Type::Artifact;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            ...$this->commonFields(),
            $this->requiredCultureField(),
            IntegerField::new('twilightCost'),
            TextField::new('strengthModifier'),
            TextField::new('vitalityModifier'),
            ChoiceField::new('subtype'),
            ...$this->trailingFields(),
        ];
    }
}
