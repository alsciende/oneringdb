<?php

declare(strict_types=1);

namespace App\Dto;

class DtoPackCard
{
    public string $cardId;
    public string $packId;
    public int $position;
    public int $quantity;
    public string $lore;
    public string $imageUrl;
}
