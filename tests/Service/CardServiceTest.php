<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\CardTypes\CompanionCard;
use App\Entity\CardTypes\SiteCard;
use App\Entity\PublishedSet;
use App\Entity\Ruleset;
use App\Enum\Culture;
use App\Enum\Rarity;
use App\Enum\Subtype;
use App\Service\CardService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CardService::class)]
class CardServiceTest extends TestCase
{
    private function makeCard(): CompanionCard
    {
        $publishedSet = new PublishedSet()->setId('01')->setName('Set')->setPosition(1);

        return new CompanionCard()
            ->setId('01001.0')
            ->setRevision(0)
            ->setPublishedSet($publishedSet)
            ->setTitle('Aragorn')
            ->setSubtitle('Dúnadan Ranger')
            ->setCulture(Culture::Gondor)
            ->setTwilightCost(3)
            ->setText('Game text')
            ->setPosition(1)
            ->setLore('Lore')
            ->setImageUrl('https://example.test/image.jpg')
            ->setRarity(Rarity::Rare)
            ->setUnique(true)
            ->setSubtype(Subtype::Man)
            ->setStrength(5)
            ->setVitality(3)
            ->setStrengthModifier('+1')
            ->setVitalityModifier('-1')
            ->setSignet('signet')
            ->setHomeSite('home site');
    }

    public function testDuplicateCopiesAllFieldsUnderTheNextRevisionId(): void
    {
        $card = $this->makeCard();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(CompanionCard::class));
        $entityManager->expects($this->once())->method('flush');

        $service = new CardService($entityManager);
        $duplicate = $service->duplicate($card);

        $this->assertInstanceOf(CompanionCard::class, $duplicate);
        $this->assertNotSame($card, $duplicate);
        $this->assertSame('01001.1', $duplicate->getId());
        $this->assertSame(1, $duplicate->getRevision());
        $this->assertSame($card->getPublishedSet(), $duplicate->getPublishedSet());
        $this->assertSame('Aragorn', $duplicate->getTitle());
        $this->assertSame('Dúnadan Ranger', $duplicate->getSubtitle());
        $this->assertSame(Culture::Gondor, $duplicate->getCulture());
        $this->assertSame(3, $duplicate->getTwilightCost());
        $this->assertSame('Game text', $duplicate->getText());
        $this->assertSame(1, $duplicate->getPosition());
        $this->assertSame('Lore', $duplicate->getLore());
        $this->assertSame('https://example.test/image.jpg', $duplicate->getImageUrl());
        $this->assertSame(Rarity::Rare, $duplicate->getRarity());
        $this->assertTrue($duplicate->isUnique());
        $this->assertSame(Subtype::Man, $duplicate->getSubtype());
        $this->assertSame(5, $duplicate->getStrength());
        $this->assertSame(3, $duplicate->getVitality());
        $this->assertSame('+1', $duplicate->getStrengthModifier());
        $this->assertSame('-1', $duplicate->getVitalityModifier());
        $this->assertSame('signet', $duplicate->getSignet());
        $this->assertSame('home site', $duplicate->getHomeSite());
        $this->assertCount(0, $duplicate->getRulesets());
    }

    public function testDuplicateAcceptsAnExplicitRevisionNumber(): void
    {
        $card = $this->makeCard();

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $service = new CardService($entityManager);
        $duplicate = $service->duplicate($card, 5);

        $this->assertSame('01001.5', $duplicate->getId());
        $this->assertSame(5, $duplicate->getRevision());
    }

    public function testDuplicatePreservesTheConcreteCardSubclass(): void
    {
        $card = new SiteCard()->setId('01100.0')->setRevision(0)
            ->setPublishedSet(new PublishedSet()->setId('01')->setName('Set')->setPosition(1))
            ->setPosition(100)
            ->setTitle('A Site')
            ->setRarity(Rarity::Common)
            ->setUnique(false);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $service = new CardService($entityManager);
        $duplicate = $service->duplicate($card);

        $this->assertInstanceOf(SiteCard::class, $duplicate);
    }

    public function testDuplicateDoesNotCarryOverTheOriginalCardsRulesets(): void
    {
        $card = $this->makeCard();
        $card->addRuleset(new Ruleset()->setName('Decipher Standard Rules')->setActive(true));

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $service = new CardService($entityManager);
        $duplicate = $service->duplicate($card);

        $this->assertCount(1, $card->getRulesets());
        $this->assertCount(0, $duplicate->getRulesets());
    }
}
