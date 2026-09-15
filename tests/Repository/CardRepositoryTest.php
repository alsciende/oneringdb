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
        $card = $repository->getCard('01001');

        $this->assertNotNull($card);
        $this->assertNull($repository->findPreviousCard($card));
    }

    public function testFindNextCardReturnsNullForTheLastCardOfASet(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $card = $repository->getCard('01365');

        $this->assertNotNull($card);
        $this->assertNull($repository->findNextCard($card));
    }

    public function testFindPreviousAndNextCardForACardInTheMiddleOfASet(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $card = $repository->getCard('01004');

        $this->assertNotNull($card);
        $this->assertSame('01003', $repository->findPreviousCard($card)?->getId());
        $this->assertSame('01005', $repository->findNextCard($card)?->getId());
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
        $card = $repository->getCard('01001');

        $this->assertNotNull($card);
        $this->assertTrue($repository->exists($card));
    }

    public function testExistsReturnsFalseForACardThatWasRemoved(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $card = $repository->getCard('01001');
        $this->assertNotNull($card);

        $card->setId('does-not-exist');

        $this->assertFalse($repository->exists($card));
    }

    public function testFindByPublishedSetReturnsOnlyCardsFromThatSet(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $card = $repository->getCard('01001');
        $this->assertNotNull($card);

        $cards = $repository->findByPublishedSet($card->getPublishedSet());

        $this->assertCount(365, $cards);
    }

    public function testSearchOrdersByAGivenSort(): void
    {
        self::bootKernel();

        /** @var CardRepository $repository */
        $repository = static::getContainer()->get(CardRepository::class);
        $cards = $repository->search('t:ring', 'twilightCost')->getQuery()->getResult();

        $this->assertNotEmpty($cards);
    }
}
