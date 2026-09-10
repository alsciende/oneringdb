<?php

declare(strict_types=1);

namespace App\Entity\CardTypes;

use App\Entity\Card;
use App\Enum\{Culture, Type};
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class FollowerCard extends Card
{
    #[\Override]
    public function getType(): Type
    {
        return Type::Follower;
    }

    /**
     * Followers have no fixture data yet: treated like Possession until proven otherwise.
     */
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
    public function hasStrengthModifier(): bool
    {
        return $this->getStrengthModifier() !== null;
    }

    #[\Override]
    public function hasVitalityModifier(): bool
    {
        return $this->getVitalityModifier() !== null;
    }
}
