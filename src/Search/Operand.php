<?php

declare(strict_types=1);

namespace App\Search;

/**
 * Possible search operands.
 */
enum Operand: string
{
    case Title = '_';
    case Culture = 'c';
    case Type = 't';
    case Subtype = 's';
    case TwilightCost = 'o';
    case Text = 'x';
    case Pack = 'p';
    case Quantity = 'q';
    case Position = 'n';
    case Lore = 'l';
}
