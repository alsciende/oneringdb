<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\CardTypes\AllyCard;
use App\Enum\Type;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AllyCardCrudController extends AbstractCardCrudController
{
    public static function getEntityFqcn(): string
    {
        return AllyCard::class;
    }

    protected function getType(): Type
    {
        return Type::Ally;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            ...$this->commonFields(),
            $this->requiredCultureField(),
            IntegerField::new('twilightCost'),
            IntegerField::new('strength'),
            IntegerField::new('vitality'),
            TextField::new('homeSite')->hideOnIndex(),
            ChoiceField::new('subtype'),
            ...$this->trailingFields(),
        ];
    }
}
