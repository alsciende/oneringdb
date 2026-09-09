<?php

declare(strict_types=1);

namespace App\Entity\CardTypes;

use App\Entity\Card;
use App\Enum\Type;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PossessionCard extends Card
{
    #[\Override]
    public function getType(): Type
    {
        return Type::Possession;
    }
}
