<?php

declare(strict_types=1);

namespace App\Entity\CardTypes;

use App\Entity\Card;
use App\Enum\{Culture, Type};
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class EventCard extends Card
{
    #[\Override]
    public function getType(): Type
    {
        return Type::Event;
    }

    #[\Override]
    public function hasCulture(): bool
    {
        return $this->getCulture() instanceof Culture;
    }

    #[\Override]
    public function hasTwilightCost(): bool
    {
        return is_int($this->getTwilightCost());
    }

    #[\Override]
    public function hasSubtype(): bool
    {
        return $this->getSubtype() !== null;
    }
}
