<?php

declare(strict_types=1);

namespace App\Entity\CardTypes;

use App\Entity\Card;
use App\Enum\Type;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class RingCard extends Card
{
    #[\Override]
    public function getType(): Type
    {
        return Type::Ring;
    }

    #[\Override]
    public function hasStrengthModifier(): bool
    {
        return $this->getStrengthModifier() !== null;
    }

    #[\Override]
    public function hasVitalityModifier(): bool
    {
        return $this->getVitalityModifier() !== null;
    }

    #[\Override]
    public function getRowTemplate(): string
    {
        return 'component/card_list/_row_ring.html.twig';
    }

    #[\Override]
    public function getTextTemplate(): string
    {
        return 'component/_card_text_ring.html.twig';
    }
}
