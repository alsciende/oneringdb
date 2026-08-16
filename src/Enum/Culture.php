<?php

declare(strict_types=1);

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum Culture: string implements TranslatableInterface
{
    case Dwarven = 'dwarven';
    case Elven = 'elven';
    case Gandalf = 'gandalf';
    case Gondor = 'gondor';
    case Isengard = 'isengard';
    case Moria = 'moria';
    case Sauron = 'sauron';
    case Shire = 'shire';
    case Wraith = 'wraith';
    case Rohan = 'rohan';
    case Gollum = 'gollum';
    case EvilMan = 'man';
    case Orc = 'orc';
    case UrukHai = 'uruk-hai';
    case Raider = 'raider';
    case Dunland = 'dunland';

    public const array SHORTHANDS = [
        'dw' => self::Dwarven,
        'el' => self::Elven,
        'ga' => self::Gandalf,
        'go' => self::Gondor,
        'is' => self::Isengard,
        'mo' => self::Moria,
        'sa' => self::Sauron,
        'sh' => self::Shire,
        'wr' => self::Wraith,
    ];

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->value, domain: 'cultures', locale: $locale);
    }
}
