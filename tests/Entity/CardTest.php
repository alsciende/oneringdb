<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Card;
use App\Entity\CardTypes\CompanionCard;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Card::class)]
class CardTest extends TestCase
{
    public function testFullTitleOfANonUniqueCardHasNoMarker(): void
    {
        $card = new CompanionCard()
            ->setTitle('Aragorn')
            ->setUnique(false);

        $this->assertSame('Aragorn', $card->getFullTitle());
    }

    public function testFullTitleOfAUniqueCardIsPrefixedWithTheUniqueSymbol(): void
    {
        $card = new CompanionCard()
            ->setTitle('Aragorn')
            ->setUnique(true);

        $this->assertSame('•Aragorn', $card->getFullTitle());
    }

    public function testFullTitleOfAUniqueCardWithASubtitle(): void
    {
        $card = new CompanionCard()
            ->setTitle('Aragorn')
            ->setSubtitle('Dúnadan Ranger')
            ->setUnique(true);

        $this->assertSame('•Aragorn, Dúnadan Ranger', $card->getFullTitle());
    }
}
