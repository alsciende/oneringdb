<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Card;
use App\Entity\CardTypes\CompanionCard;
use App\Entity\CardTypes\RingCard;
use App\Entity\CardTypes\SiteCard;
use App\Entity\PublishedSet;
use App\Enum\Rarity;
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

    public function testSemiTitleOfANonUniqueCardHasNoMarker(): void
    {
        $card = new CompanionCard()->setTitle('Aragorn')->setUnique(false);

        $this->assertSame('Aragorn', $card->getSemiTitle());
    }

    public function testSemiTitleOfAUniqueCardIsPrefixedWithTheUniqueSymbol(): void
    {
        $card = new CompanionCard()->setTitle('Aragorn')->setUnique(true);

        $this->assertSame('•Aragorn', $card->getSemiTitle());
    }

    public function testHasSubtitleIsFalseByDefault(): void
    {
        $card = new CompanionCard()->setTitle('Aragorn')->setUnique(false);

        $this->assertFalse($card->hasSubtitle());
        $this->assertNull($card->getSubtitle());
    }

    public function testHasSubtitleIsTrueWhenSet(): void
    {
        $card = new CompanionCard()->setTitle('Aragorn')->setUnique(false)->setSubtitle('Dúnadan Ranger');

        $this->assertTrue($card->hasSubtitle());
        $this->assertSame('Dúnadan Ranger', $card->getSubtitle());
    }

    public function testDefaultCapabilitiesAreAllFalseUnlessOverridden(): void
    {
        $card = new RingCard()->setTitle('The One Ring')->setUnique(true);

        $this->assertFalse($card->hasCulture());
        $this->assertFalse($card->hasTwilightCost());
        $this->assertFalse($card->hasStrength());
        $this->assertFalse($card->hasVitality());
        $this->assertFalse($card->hasSubtype());
        $this->assertFalse($card->hasSiteNumber());
        $this->assertFalse($card->hasShadowNumber());
        $this->assertFalse($card->hasSignet());
        $this->assertFalse($card->hasHomeSite());

        $this->assertNull($card->getCulture());
        $this->assertNull($card->getTwilightCost());
        $this->assertNull($card->getStrength());
        $this->assertNull($card->getVitality());
        $this->assertNull($card->getSubtype());
        $this->assertNull($card->getSiteNumber());
        $this->assertNull($card->getShadowNumber());
        $this->assertNull($card->getSignet());
        $this->assertNull($card->getHomeSite());
    }

    public function testDefaultModifierCapabilitiesAreAllFalseUnlessOverridden(): void
    {
        $card = new SiteCard();

        $this->assertFalse($card->hasStrengthModifier());
        $this->assertFalse($card->hasVitalityModifier());
        $this->assertFalse($card->hasSiteNumberModifier());

        $this->assertNull($card->getStrengthModifier());
        $this->assertNull($card->getVitalityModifier());
        $this->assertNull($card->getSiteNumberModifier());
    }

    public function testDefaultTemplates(): void
    {
        $card = new CompanionCard();

        $this->assertSame('component/card_list/row/_default.html.twig', $card->getRowTemplate());
        $this->assertSame('component/card_list/text/_default.html.twig', $card->getTextTemplate());
        $this->assertSame('component/card/complete/_default.html.twig', $card->getDetailTemplate());
    }

    public function testGetCollectorInfo(): void
    {
        $publishedSet = new PublishedSet()->setId('01')->setName('Set')->setPosition(7);
        $card = new CompanionCard()
            ->setTitle('Aragorn')
            ->setPosition(42)
            ->setRarity(Rarity::Rare);
        $publishedSet->addCard($card);

        $this->assertSame("7\u{202F}R\u{202F}42", $card->getCollectorInfo());
    }

    public function testToArray(): void
    {
        $publishedSet = new PublishedSet()->setId('01')->setName('Set')->setPosition(1);
        $card = new CompanionCard()
            ->setId('01001')
            ->setTitle('Aragorn')
            ->setSubtitle('Dúnadan Ranger')
            ->setText('Text')
            ->setPosition(1)
            ->setLore('Lore')
            ->setImageUrl('https://example.test/image.jpg')
            ->setRarity(Rarity::Rare)
            ->setUnique(true)
            ->setStrength(5)
            ->setVitality(3);
        $publishedSet->addCard($card);

        $array = $card->toArray();

        $this->assertSame('01001', $array['id']);
        $this->assertSame('Aragorn', $array['title']);
        $this->assertSame('Dúnadan Ranger', $array['subtitle']);
        $this->assertSame('companion', $array['type']);
        $this->assertSame('Text', $array['text']);
        $this->assertSame('01', $array['published_set_id']);
        $this->assertSame(1, $array['position']);
        $this->assertSame('Lore', $array['lore']);
        $this->assertSame('https://example.test/image.jpg', $array['image_url']);
        $this->assertSame('rare', $array['rarity']);
        $this->assertTrue($array['unique']);
        $this->assertSame(5, $array['strength']);
        $this->assertSame(3, $array['vitality']);
    }

    public function testToString(): void
    {
        $card = new CompanionCard()->setId('01001')->setTitle('Aragorn')->setUnique(true);

        $this->assertSame('•Aragorn (#01001)', (string) $card);
    }
}
