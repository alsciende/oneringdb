<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\CardTypes\SiteCard;
use App\Enum\Type;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class SiteCardCrudController extends AbstractCardCrudController
{
    public static function getEntityFqcn(): string
    {
        return SiteCard::class;
    }

    protected function getType(): Type
    {
        return Type::Site;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            ...$this->commonFields(),
            IntegerField::new('siteNumber'),
            IntegerField::new('shadowNumber'),
            ...$this->trailingFields(),
        ];
    }
}
