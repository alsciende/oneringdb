<?php

declare(strict_types=1);

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum Subtype: string implements TranslatableInterface
{
    case Armor = 'Armor';
    case Balrog = 'Balrog';
    case Bracers = 'Bracers';
    case Brooch = 'Brooch';
    case Cloak = 'Cloak';
    case Creature = 'Creature';
    case Dwarf = 'Dwarf';
    case Elf = 'Elf';
    case Ent = 'Ent';
    case Gauntlets = 'Gauntlets';
    case HandWeapon = 'Hand Weapon';
    case Helm = 'Helm';
    case Hobbit = 'Hobbit';
    case Man = 'Man';
    case Mount = 'Mount';
    case Nazgul = 'Nazgûl';
    case Orc = 'Orc';
    case Pipe = 'Pipe';
    case RangedWeapon = 'Ranged Weapon';
    case Ring = 'Ring';
    case Shield = 'Shield';
    case Spider = 'Spider';
    case Staff = 'Staff';
    case SupportArea = 'Support Area';
    case Troll = 'Troll';
    case UrukHai = 'Uruk-hai';
    case Wizard = 'Wizard';
    case Wraith = 'Wraith';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->value, domain: 'subtypes', locale: $locale);
    }
}
