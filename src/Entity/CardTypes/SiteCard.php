<?php

declare(strict_types=1);

namespace App\Entity\CardTypes;

use App\Entity\Card;
use App\Enum\Type;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class SiteCard extends Card
{
    #[\Override]
    public function getType(): Type
    {
        return Type::Site;
    }

    #[\Override]
    public function hasSiteNumber(): bool
    {
        return is_int($this->getSiteNumber());
    }

    #[\Override]
    public function hasShadowNumber(): bool
    {
        return is_int($this->getShadowNumber());
    }

    #[\Override]
    public function getRowTemplate(): string
    {
        return 'component/card_list/row/_site.html.twig';
    }

    #[\Override]
    public function getTextTemplate(): string
    {
        return 'component/card_list/text/_site.html.twig';
    }

    #[\Override]
    public function getDetailTemplate(): string
    {
        return 'component/card/complete/_site.html.twig';
    }
}
