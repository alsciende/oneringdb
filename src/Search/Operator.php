<?php

declare(strict_types=1);

namespace App\Search;

/**
 * Possible search operators.
 */
enum Operator: string
{
    case EQ = ':';
    case NE = '!';
    case LT = '<';
    case GT = '>';
}
