<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\CardTypes\EventCard;
use App\Enum\Type;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class EventCardCrudController extends AbstractCardCrudController
{
    public static function getEntityFqcn(): string
    {
        return EventCard::class;
    }

    protected function getType(): Type
    {
        return Type::Event;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            ...$this->commonFields(),
            $this->requiredCultureField(),
            IntegerField::new('twilightCost'),
            ChoiceField::new('subtype'),
            ...$this->trailingFields(),
        ];
    }
}
