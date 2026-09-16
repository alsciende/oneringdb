<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\CardTypes\CompanionCard;
use App\Entity\Ruleset;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Ruleset::class)]
class RulesetTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $date = new \DateTime('2001-11-06');
        $ruleset = new Ruleset()->setName('Decipher Standard Rules')->setActive(true)->setPublicationDate($date);

        $this->assertSame('Decipher Standard Rules', $ruleset->getName());
        $this->assertTrue($ruleset->isActive());
        $this->assertEquals($date, $ruleset->getPublicationDate());
    }

    public function testIsActiveIsFalseWhenNull(): void
    {
        $ruleset = new Ruleset()->setName('Errata 2024')->setActive(null);

        $this->assertFalse($ruleset->isActive());
    }

    public function testSetPublicationDateCopiesTheGivenDate(): void
    {
        $date = new \DateTime('2001-11-06');
        $ruleset = new Ruleset()->setName('Decipher Standard Rules')->setPublicationDate($date);

        $this->assertNotSame($date, $ruleset->getPublicationDate());
        $this->assertEquals($date, $ruleset->getPublicationDate());
    }

    public function testSetPublicationDateAcceptsNull(): void
    {
        $ruleset = new Ruleset()->setName('Decipher Standard Rules')->setPublicationDate(null);

        $this->assertNull($ruleset->getPublicationDate());
    }

    public function testCardsCollectionStartsEmpty(): void
    {
        $ruleset = new Ruleset();

        $this->assertCount(0, $ruleset->getCards());
    }

    public function testAddCardAttachesBothSides(): void
    {
        $ruleset = new Ruleset()->setName('Decipher Standard Rules')->setActive(true);
        $card = new CompanionCard()->setTitle('Aragorn');

        $ruleset->addCard($card);

        $this->assertCount(1, $ruleset->getCards());
        $this->assertTrue($card->getRulesets()->contains($ruleset));
    }

    public function testAddCardIsIdempotent(): void
    {
        $ruleset = new Ruleset()->setName('Decipher Standard Rules')->setActive(true);
        $card = new CompanionCard()->setTitle('Aragorn');

        $ruleset->addCard($card);
        $ruleset->addCard($card);

        $this->assertCount(1, $ruleset->getCards());
    }

    public function testRemoveCardDetachesBothSides(): void
    {
        $ruleset = new Ruleset()->setName('Decipher Standard Rules')->setActive(true);
        $card = new CompanionCard()->setTitle('Aragorn');
        $ruleset->addCard($card);

        $ruleset->removeCard($card);

        $this->assertCount(0, $ruleset->getCards());
        $this->assertFalse($card->getRulesets()->contains($ruleset));
    }

    public function testToString(): void
    {
        $ruleset = new Ruleset()->setName('Decipher Standard Rules');

        $this->assertSame('Decipher Standard Rules', (string) $ruleset);
    }
}
