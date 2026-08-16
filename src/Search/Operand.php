<?php

declare(strict_types=1);

namespace App\Search;

/**
 * Possible search operands.
 */
enum Operand: string
{
    case Name = '_';
    case Culture = 'c';
    case Type = 't';
    case TwilightCost = 'w';
    case Text = 'x';
    case Pack = 'p';
    case Quantity = 'q';
    case Position = 'n';
    case Lore = 'l';
}
