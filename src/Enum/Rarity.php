<?php

declare(strict_types=1);

namespace App\Enum;

enum Rarity: string
{
    case Common = 'common';
    case Uncommon = 'uncommon';
    case Rare = 'rare';
    case Promotional = 'promotional';

    public const array CODES = [
        'C' => self::Common,
        'U' => self::Uncommon,
        'R' => self::Rare,
        'P' => self::Promotional,
    ];
}
