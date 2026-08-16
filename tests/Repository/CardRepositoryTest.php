<?php

declare(strict_types=1);

namespace App\Tests\Repository;

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
            ['sphere', 1],
            ['sphere f:neogenesis-church', 1],
            ['sphere f:neogenesis-church t:facility', 1],
            ['sphere f:coriolan-exiles', 0],
            ['sphere f:neogenesis-church t:facility', 1],
            ['baptists f:neogenesis-church t:unit s:human', 1],
            ['baptists a:1', 1],
            ['baptists a:1 r:1', 1],
            ['baptists a:1 r:1 h:5', 1],
            ['baptists a:1 r:1 h:5 c:3', 1],
            ['x:"growth phase"', 31],
            ['s:human s:xeno', 1],
            ['r:1', 71],
            ['r>1', 17],
            ['r<1', 42],
            ['r!1', 59],
            ['a:1', 46],
            ['a>1', 72],
            ['a<1', 12],
            ['a!1', 84],
            ['c:3', 85],
            ['c>3', 106],
            ['c<3', 109],
            ['c!3', 215],
            ['f!ub f!ce f!nc f!ca f!td f!ur f!ms f!md f!wc f!rh', 0],
            ['h:3', 49],
            ['h>3', 52],
            ['h<3', 29],
            ['h!3', 81],
            ['i:"Manthos Lappas"', 38],
            ['i!"Manthos Lappas"', 272],
            ['_!sphere', 309],
            ['p!1st', 0],
            ['t:unit s!human s!xeno s!mech s!synth s!chimera s!antigrav', 0],
            ['x:armor x:attack x!cloak x!pierce', 10],
            ['t!unit t!facility t!augment t!reflex t!leader', 0],
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
}
