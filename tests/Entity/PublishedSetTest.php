<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\CardTypes\CompanionCard;
use App\Entity\PublishedSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PublishedSet::class)]
class PublishedSetTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $date = new \DateTime('2001-10-08');
        $set = new PublishedSet()
            ->setId('01')
            ->setName('The Fellowship of the Ring')
            ->setSize(365)
            ->setPosition(1)
            ->setShorthand('FotR')
            ->setReleaseDate($date);

        $this->assertSame('01', $set->getId());
        $this->assertSame('The Fellowship of the Ring', $set->getName());
        $this->assertSame(365, $set->getSize());
        $this->assertSame(1, $set->getPosition());
        $this->assertSame('FotR', $set->getShorthand());
        $this->assertEquals($date, $set->getReleaseDate());
    }

    public function testSetReleaseDateCopiesTheGivenDate(): void
    {
        $date = new \DateTime('2001-10-08');
        $set = new PublishedSet()->setId('01')->setName('Set')->setPosition(1)->setReleaseDate($date);

        $this->assertNotSame($date, $set->getReleaseDate());
        $this->assertEquals($date, $set->getReleaseDate());
    }

    public function testSetReleaseDateAcceptsNull(): void
    {
        $set = new PublishedSet()->setId('01')->setName('Set')->setPosition(1)->setReleaseDate(null);

        $this->assertNull($set->getReleaseDate());
    }

    public function testCardsCollectionStartsEmpty(): void
    {
        $set = new PublishedSet();

        $this->assertCount(0, $set->getCards());
    }

    public function testAddCardAttachesTheCardToTheSet(): void
    {
        $set = new PublishedSet()->setId('01')->setName('Set')->setPosition(1);
        $card = new CompanionCard()->setTitle('Aragorn');

        $set->addCard($card);

        $this->assertCount(1, $set->getCards());
        $this->assertSame($set, $card->getPublishedSet());
    }

    public function testAddCardIsIdempotent(): void
    {
        $set = new PublishedSet()->setId('01')->setName('Set')->setPosition(1);
        $card = new CompanionCard()->setTitle('Aragorn');

        $set->addCard($card);
        $set->addCard($card);

        $this->assertCount(1, $set->getCards());
    }

    public function testRemoveCardDetachesTheCard(): void
    {
        $set = new PublishedSet()->setId('01')->setName('Set')->setPosition(1);
        $card = new CompanionCard()->setTitle('Aragorn');
        $set->addCard($card);

        $set->removeCard($card);

        $this->assertCount(0, $set->getCards());
    }

    public function testGetCardAtReturnsTheCardByIndex(): void
    {
        $set = new PublishedSet()->setId('01')->setName('Set')->setPosition(1);
        $card = new CompanionCard()->setTitle('Aragorn');
        $set->addCard($card);

        $this->assertSame($card, $set->getCardAt(0));
    }

    public function testToArray(): void
    {
        $date = new \DateTime('2001-10-08');
        $set = new PublishedSet()
            ->setId('01')
            ->setName('The Fellowship of the Ring')
            ->setSize(365)
            ->setPosition(1)
            ->setReleaseDate($date);

        $this->assertSame([
            'id' => '01',
            'position' => 1,
            'name' => 'The Fellowship of the Ring',
            'size' => 365,
            'releaseDate' => $set->getReleaseDate(),
        ], $set->toArray());
    }

    public function testToString(): void
    {
        $set = new PublishedSet()->setId('01')->setName('Set')->setPosition(1)->setShorthand('FotR');

        $this->assertSame('Set (#FotR)', (string) $set);
    }
}
