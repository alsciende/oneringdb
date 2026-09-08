<?php

declare(strict_types=1);

namespace App\Dto;

class DtoCard
{
    public string $file = '';

    public string $setNumber = '';

    public string $rarity = '';

    public string $cardNumber = '';

    public string $name = '';

    public ?string $subtitle = null;

    public bool $unique = false;

    public string $type = '';

    public ?string $subtype = null;

    public ?string $culture = null;

    public ?string $twilightCost = null;

    public ?string $gameText = null;

    public ?string $flavorText = null;

    public ?string $strength = null;

    public ?string $vitality = null;

    public ?string $strengthModifier = null;

    public ?string $vitalityModifier = null;

    public ?string $siteNumber = null;

    public ?string $shadowNumber = null;

    public ?string $signet = null;

    public ?string $homeSite = null;

    public ?string $siteNumberModifier = null;
}
