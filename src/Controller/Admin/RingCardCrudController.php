<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\CardTypes\RingCard;
use App\Enum\Type;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class RingCardCrudController extends AbstractCardCrudController
{
    public static function getEntityFqcn(): string
    {
        return RingCard::class;
    }

    protected function getType(): Type
    {
        return Type::Ring;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            ...$this->commonFields(),
            TextField::new('strengthModifier'),
            TextField::new('vitalityModifier'),
            ...$this->trailingFields(),
        ];
    }
}
