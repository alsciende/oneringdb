<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\CardTypes\CompanionCard;
use App\Enum\Type;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CompanionCardCrudController extends AbstractCardCrudController
{
    public static function getEntityFqcn(): string
    {
        return CompanionCard::class;
    }

    protected function getType(): Type
    {
        return Type::Companion;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            ...$this->commonFields(),
            $this->requiredCultureField(),
            IntegerField::new('twilightCost'),
            IntegerField::new('strength'),
            IntegerField::new('vitality'),
            TextField::new('signet')->hideOnIndex(),
            ChoiceField::new('subtype'),
            ...$this->trailingFields(),
        ];
    }
}
