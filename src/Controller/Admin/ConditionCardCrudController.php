<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\CardTypes\ConditionCard;
use App\Enum\Type;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ConditionCardCrudController extends AbstractCardCrudController
{
    public static function getEntityFqcn(): string
    {
        return ConditionCard::class;
    }

    protected function getType(): Type
    {
        return Type::Condition;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            ...$this->commonFields(),
            $this->requiredCultureField(),
            IntegerField::new('twilightCost'),
            TextField::new('strengthModifier'),
            TextField::new('vitalityModifier'),
            TextField::new('siteNumberModifier'),
            ChoiceField::new('subtype'),
            ...$this->trailingFields(),
        ];
    }
}
