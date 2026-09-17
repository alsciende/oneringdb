<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\CardTypes\MinionCard;
use App\Enum\Type;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class MinionCardCrudController extends AbstractCardCrudController
{
    public static function getEntityFqcn(): string
    {
        return MinionCard::class;
    }

    protected function getType(): Type
    {
        return Type::Minion;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            ...$this->commonFields(),
            $this->requiredCultureField(),
            IntegerField::new('twilightCost'),
            IntegerField::new('strength'),
            IntegerField::new('vitality'),
            IntegerField::new('siteNumber'),
            ChoiceField::new('subtype'),
            ...$this->trailingFields(),
        ];
    }
}
