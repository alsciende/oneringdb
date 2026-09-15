<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Enum\Rarity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rarity::class)]
class RarityTest extends TestCase
{
    /**
     * @return array<array{Rarity, string}>
     */
    public static function codeProvider(): array
    {
        return [
            [Rarity::Common, 'C'],
            [Rarity::Uncommon, 'U'],
            [Rarity::Rare, 'R'],
            [Rarity::Promotional, 'P'],
        ];
    }

    #[DataProvider('codeProvider')]
    public function testGetCode(Rarity $rarity, string $code): void
    {
        $this->assertSame($code, $rarity->getCode());
    }
}
