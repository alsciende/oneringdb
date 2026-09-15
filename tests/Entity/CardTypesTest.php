<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Card;
use App\Entity\CardTypes\AllyCard;
use App\Entity\CardTypes\ArtifactCard;
use App\Entity\CardTypes\CompanionCard;
use App\Entity\CardTypes\ConditionCard;
use App\Entity\CardTypes\EventCard;
use App\Entity\CardTypes\FollowerCard;
use App\Entity\CardTypes\MinionCard;
use App\Entity\CardTypes\PossessionCard;
use App\Entity\CardTypes\RingCard;
use App\Entity\CardTypes\SiteCard;
use App\Enum\Culture;
use App\Enum\Subtype;
use App\Enum\Type;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(AllyCard::class)]
#[CoversClass(ArtifactCard::class)]
#[CoversClass(CompanionCard::class)]
#[CoversClass(ConditionCard::class)]
#[CoversClass(EventCard::class)]
#[CoversClass(FollowerCard::class)]
#[CoversClass(MinionCard::class)]
#[CoversClass(PossessionCard::class)]
#[CoversClass(RingCard::class)]
#[CoversClass(SiteCard::class)]
class CardTypesTest extends TestCase
{
    /**
     * @return array<array{class-string<Card>, Type}>
     */
    public static function typeProvider(): array
    {
        return [
            [AllyCard::class, Type::Ally],
            [ArtifactCard::class, Type::Artifact],
            [CompanionCard::class, Type::Companion],
            [ConditionCard::class, Type::Condition],
            [EventCard::class, Type::Event],
            [FollowerCard::class, Type::Follower],
            [MinionCard::class, Type::Minion],
            [PossessionCard::class, Type::Possession],
            [RingCard::class, Type::Ring],
            [SiteCard::class, Type::Site],
        ];
    }

    /**
     * @param class-string<Card> $class
     */
    #[DataProvider('typeProvider')]
    public function testGetType(string $class, Type $type): void
    {
        $this->assertSame($type, new $class()->getType());
    }

    public function testAllyCardCapabilities(): void
    {
        $card = new AllyCard();

        $this->assertFalse($card->hasCulture());
        $this->assertFalse($card->hasTwilightCost());
        $this->assertFalse($card->hasStrength());
        $this->assertFalse($card->hasVitality());
        $this->assertFalse($card->hasHomeSite());
        $this->assertFalse($card->hasSubtype());

        $card->setCulture(Culture::Shire)->setTwilightCost(1)->setStrength(1)->setVitality(1)->setHomeSite('Bag End')->setSubtype(Subtype::Hobbit);

        $this->assertTrue($card->hasCulture());
        $this->assertTrue($card->hasTwilightCost());
        $this->assertTrue($card->hasStrength());
        $this->assertTrue($card->hasVitality());
        $this->assertTrue($card->hasHomeSite());
        $this->assertTrue($card->hasSubtype());
    }

    public function testArtifactCardCapabilities(): void
    {
        $card = new ArtifactCard();

        $this->assertFalse($card->hasCulture());
        $this->assertFalse($card->hasTwilightCost());
        $this->assertFalse($card->hasStrengthModifier());
        $this->assertFalse($card->hasVitalityModifier());
        $this->assertFalse($card->hasSubtype());

        $card->setCulture(Culture::Shire)->setTwilightCost(1)->setStrengthModifier('+1')->setVitalityModifier('+1')->setSubtype(Subtype::Ring);

        $this->assertTrue($card->hasCulture());
        $this->assertTrue($card->hasTwilightCost());
        $this->assertTrue($card->hasStrengthModifier());
        $this->assertTrue($card->hasVitalityModifier());
        $this->assertTrue($card->hasSubtype());
    }

    public function testCompanionCardCapabilities(): void
    {
        $card = new CompanionCard();

        $this->assertFalse($card->hasCulture());
        $this->assertFalse($card->hasTwilightCost());
        $this->assertFalse($card->hasStrength());
        $this->assertFalse($card->hasVitality());
        $this->assertFalse($card->hasSignet());
        $this->assertFalse($card->hasSubtype());

        $card->setCulture(Culture::Shire)->setTwilightCost(1)->setStrength(1)->setVitality(1)->setSignet('signet')->setSubtype(Subtype::Hobbit);

        $this->assertTrue($card->hasCulture());
        $this->assertTrue($card->hasTwilightCost());
        $this->assertTrue($card->hasStrength());
        $this->assertTrue($card->hasVitality());
        $this->assertTrue($card->hasSignet());
        $this->assertTrue($card->hasSubtype());
    }

    public function testConditionCardCapabilities(): void
    {
        $card = new ConditionCard();

        $this->assertFalse($card->hasCulture());
        $this->assertFalse($card->hasTwilightCost());
        $this->assertFalse($card->hasStrengthModifier());
        $this->assertFalse($card->hasVitalityModifier());
        $this->assertFalse($card->hasSiteNumberModifier());
        $this->assertFalse($card->hasSubtype());

        $card->setCulture(Culture::Shire)->setTwilightCost(1)->setStrengthModifier('+1')->setVitalityModifier('+1')->setSiteNumberModifier('+1')->setSubtype(Subtype::Hobbit);

        $this->assertTrue($card->hasCulture());
        $this->assertTrue($card->hasTwilightCost());
        $this->assertTrue($card->hasStrengthModifier());
        $this->assertTrue($card->hasVitalityModifier());
        $this->assertTrue($card->hasSiteNumberModifier());
        $this->assertTrue($card->hasSubtype());
    }

    public function testEventCardCapabilities(): void
    {
        $card = new EventCard();

        $this->assertFalse($card->hasCulture());
        $this->assertFalse($card->hasTwilightCost());
        $this->assertFalse($card->hasSubtype());

        $card->setCulture(Culture::Shire)->setTwilightCost(1)->setSubtype(Subtype::Hobbit);

        $this->assertTrue($card->hasCulture());
        $this->assertTrue($card->hasTwilightCost());
        $this->assertTrue($card->hasSubtype());
    }

    public function testFollowerCardCapabilities(): void
    {
        $card = new FollowerCard();

        $this->assertFalse($card->hasCulture());
        $this->assertFalse($card->hasTwilightCost());
        $this->assertFalse($card->hasStrengthModifier());
        $this->assertFalse($card->hasVitalityModifier());
        $this->assertFalse($card->hasSubtype());

        $card->setCulture(Culture::Shire)->setTwilightCost(1)->setStrengthModifier('+1')->setVitalityModifier('+1')->setSubtype(Subtype::Hobbit);

        $this->assertTrue($card->hasCulture());
        $this->assertTrue($card->hasTwilightCost());
        $this->assertTrue($card->hasStrengthModifier());
        $this->assertTrue($card->hasVitalityModifier());
        $this->assertTrue($card->hasSubtype());
    }

    public function testMinionCardCapabilities(): void
    {
        $card = new MinionCard();

        $this->assertFalse($card->hasCulture());
        $this->assertFalse($card->hasTwilightCost());
        $this->assertFalse($card->hasStrength());
        $this->assertFalse($card->hasVitality());
        $this->assertFalse($card->hasSiteNumber());
        $this->assertFalse($card->hasSubtype());

        $card->setCulture(Culture::Shire)->setTwilightCost(1)->setStrength(1)->setVitality(1)->setSiteNumber(1)->setSubtype(Subtype::Orc);

        $this->assertTrue($card->hasCulture());
        $this->assertTrue($card->hasTwilightCost());
        $this->assertTrue($card->hasStrength());
        $this->assertTrue($card->hasVitality());
        $this->assertTrue($card->hasSiteNumber());
        $this->assertTrue($card->hasSubtype());
    }

    public function testPossessionCardCapabilities(): void
    {
        $card = new PossessionCard();

        $this->assertFalse($card->hasCulture());
        $this->assertFalse($card->hasTwilightCost());
        $this->assertFalse($card->hasStrengthModifier());
        $this->assertFalse($card->hasVitalityModifier());
        $this->assertFalse($card->hasSubtype());

        $card->setCulture(Culture::Shire)->setTwilightCost(1)->setStrengthModifier('+1')->setVitalityModifier('+1')->setSubtype(Subtype::Ring);

        $this->assertTrue($card->hasCulture());
        $this->assertTrue($card->hasTwilightCost());
        $this->assertTrue($card->hasStrengthModifier());
        $this->assertTrue($card->hasVitalityModifier());
        $this->assertTrue($card->hasSubtype());
    }

    public function testRingCardCapabilitiesAndTemplates(): void
    {
        $card = new RingCard();

        $this->assertFalse($card->hasStrengthModifier());
        $this->assertFalse($card->hasVitalityModifier());

        $card->setStrengthModifier('+1')->setVitalityModifier('+1');

        $this->assertTrue($card->hasStrengthModifier());
        $this->assertTrue($card->hasVitalityModifier());

        $this->assertSame('component/card_list/row/_ring.html.twig', $card->getRowTemplate());
        $this->assertSame('component/card_list/text/_ring.html.twig', $card->getTextTemplate());
        $this->assertSame('component/card/complete/_ring.html.twig', $card->getDetailTemplate());
    }

    public function testSiteCardCapabilitiesAndTemplates(): void
    {
        $card = new SiteCard();

        $this->assertFalse($card->hasSiteNumber());
        $this->assertFalse($card->hasShadowNumber());

        $card->setSiteNumber(1)->setShadowNumber(1);

        $this->assertTrue($card->hasSiteNumber());
        $this->assertTrue($card->hasShadowNumber());

        $this->assertSame('component/card_list/row/_site.html.twig', $card->getRowTemplate());
        $this->assertSame('component/card_list/text/_site.html.twig', $card->getTextTemplate());
        $this->assertSame('component/card/complete/_site.html.twig', $card->getDetailTemplate());
    }
}
