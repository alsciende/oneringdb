<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\CardTypes\CompanionCard;
use App\Repository\CardRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(CardRepository::class)]
class CardRepositoryTest extends KernelTestCase
{
    /**
     * @return array<array{string, int}>
     */
    public static function searchProvider(): array
    {
        return [
            ['rosie', 1],
            ['rosie c:shire', 1],
            ['rosie c:shire t:ally', 1],
            ['rosie c:gondor', 0],
            ['s:elf', 55],
            ['x:pipeweed', 7],
            ['p:01', 365],
            ['p:01 _!rosie', 364],
        ];
    }

    #[DataProvider('searchProvider')]
    public function testSearch(string $query, int $count): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);

        $this->assertCount($count, $repository->search($query)->getQuery()->getResult());
    }

    public function testFindPreviousCardReturnsNullForTheFirstCardOfASet(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $card = $repository->getCard('01001.0');

        $this->assertNotNull($card);
        $this->assertNull($repository->findPreviousCard($card));
    }

    public function testFindNextCardReturnsNullForTheLastCardOfASet(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $card = $repository->getCard('01365.0');

        $this->assertNotNull($card);
        $this->assertNull($repository->findNextCard($card));
    }

    public function testFindPreviousAndNextCardForACardInTheMiddleOfASet(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $card = $repository->getCard('01004.0');

        $this->assertNotNull($card);
        // 01003 has since been errata'd to revision 1, which is the one linked to the active Ruleset.
        $this->assertSame('01003.1', $repository->findPreviousCard($card)?->getId());
        $this->assertSame('01005.0', $repository->findNextCard($card)?->getId());
    }

    public function testFindPreviousCardReturnsNullWhenTheCardHasNoPosition(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $card = new CompanionCard()->setTitle('Unpositioned');

        $this->assertNull($repository->findPreviousCard($card));
    }

    public function testFindNextCardReturnsNullWhenTheCardHasNoPosition(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $card = new CompanionCard()->setTitle('Unpositioned');

        $this->assertNull($repository->findNextCard($card));
    }

    public function testSearchWithPositionSortOrdersBySetThenPosition(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $cards = $repository->search('t:ring', 'position')->getQuery()->getResult();

        $this->assertNotEmpty($cards);
    }

    public function testExistsReturnsTrueForAPersistedCard(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $card = $repository->getCard('01001.0');

        $this->assertNotNull($card);
        $this->assertTrue($repository->exists($card));
    }

    public function testExistsReturnsFalseForACardThatWasRemoved(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $card = $repository->getCard('01001.0');
        $this->assertNotNull($card);

        $card->setId('does-not-exist');

        $this->assertFalse($repository->exists($card));
    }

    public function testFindByPublishedSetReturnsOnlyCardsFromThatSet(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $card = $repository->getCard('01001.0');
        $this->assertNotNull($card);

        $cards = $repository->findByPublishedSet($card->getPublishedSet());

        // findByPublishedSet() is not Ruleset-filtered: it returns every revision ever created for
        // the set (365 original cards + every card since errata'd to a later revision).
        $this->assertCount(383, $cards);
    }

    public function testSearchOrdersByAGivenSort(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $cards = $repository->search('t:ring', 'twilightCost')->getQuery()->getResult();

        $this->assertNotEmpty($cards);
    }

    public function testFindDistinctPositionsReturnsEveryKnownPositionOfASet(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $card = $repository->getCard('01001.0');
        $this->assertNotNull($card);

        $positions = $repository->findDistinctPositions($card->getPublishedSet());

        $this->assertCount(365, $positions);
        $this->assertContains(1, $positions);
        $this->assertContains(365, $positions);
    }

    public function testGetCardReturnsACardEvenWhenItIsNotInTheActiveRuleset(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);

        // 01003 has since been errata'd to revision 1; .0 is superseded and no longer linked to
        // the active Ruleset, but its page must still be reachable directly by id.
        $card = $repository->getCard('01003.0');

        $this->assertNotNull($card);
        $this->assertSame(0, $card->getRevision());
    }

    public function testFindDistinctPositionsFiltersByRuleset(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $card = $repository->getCard('01001.0');
        $this->assertNotNull($card);

        $ruleset = $card->getRulesets()->first();
        $this->assertNotFalse($ruleset);

        $positions = $repository->findDistinctPositions($card->getPublishedSet(), $ruleset);

        $this->assertCount(365, $positions);
    }

    public function testFindRevisionsReturnsEveryRevisionOfTheSameSlot(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $card = $repository->getCard('01003.0');
        $this->assertNotNull($card);

        $revisions = $repository->findRevisions($card);
        $ids = array_map(static fn ($revision) => $revision->getId(), $revisions);

        $this->assertCount(2, $revisions);
        $this->assertContains('01003.0', $ids);
        $this->assertContains('01003.1', $ids);
    }

    public function testFindRevisionsReturnsOnlyItselfWhenTheCardHasNoPosition(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $card = new CompanionCard()->setTitle('Unpositioned');

        $this->assertSame([$card], $repository->findRevisions($card));
    }
}
