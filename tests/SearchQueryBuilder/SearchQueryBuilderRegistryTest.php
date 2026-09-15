<?php

declare(strict_types=1);

namespace App\Tests\SearchQueryBuilder;

use App\Search\Operand;
use App\SearchQueryBuilder\Builder\PackSearchQueryBuilder;
use App\SearchQueryBuilder\Builder\TitleSearchQueryBuilder;
use App\SearchQueryBuilder\SearchQueryBuilderRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchQueryBuilderRegistry::class)]
class SearchQueryBuilderRegistryTest extends TestCase
{
    public function testGetNamesAndGetSearchQueryBuilders(): void
    {
        $title = new TitleSearchQueryBuilder();
        $pack = new PackSearchQueryBuilder();
        $registry = new SearchQueryBuilderRegistry([$title, $pack]);

        $this->assertSame(['_', 'p'], $registry->getNames());
        $this->assertSame([
            '_' => $title,
            'p' => $pack,
        ], $registry->getSearchQueryBuilders());
    }

    public function testGetSearchQueryBuilderReturnsTheMatchingService(): void
    {
        $title = new TitleSearchQueryBuilder();
        $registry = new SearchQueryBuilderRegistry([$title]);

        $this->assertSame($title, $registry->getSearchQueryBuilder(Operand::Title));
    }

    public function testGetSearchQueryBuilderThrowsWhenNotFound(): void
    {
        $registry = new SearchQueryBuilderRegistry([]);

        $this->expectException(\UnexpectedValueException::class);
        $registry->getSearchQueryBuilder(Operand::Title);
    }

    public function testConstructorThrowsOnDuplicateNames(): void
    {
        $this->expectException(\LogicException::class);
        new SearchQueryBuilderRegistry([new TitleSearchQueryBuilder(), new TitleSearchQueryBuilder()]);
    }
}
