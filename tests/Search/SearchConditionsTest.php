<?php

declare(strict_types=1);

namespace App\Tests\Search;

use App\Search\CardCondition;
use App\Search\Operand;
use App\Search\Operator;
use App\Search\SearchConditions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchConditions::class)]
class SearchConditionsTest extends TestCase
{
    public function testWithConditionsBuildsFromAnArray(): void
    {
        $condition = new CardCondition('rosie', Operand::Title, Operator::EQ);
        $search = SearchConditions::withConditions([$condition]);

        $this->assertSame([$condition], $search->getConditions());
    }

    public function testAddConditionAppendsToTheList(): void
    {
        $search = new SearchConditions();
        $first = new CardCondition('rosie', Operand::Title, Operator::EQ);
        $second = new CardCondition('shire', Operand::Culture, Operator::EQ);

        $search->addCondition($first);
        $search->addCondition($second);

        $this->assertSame([$first, $second], $search->getConditions());
    }

    public function testGetConditionsIsEmptyByDefault(): void
    {
        $this->assertSame([], new SearchConditions()->getConditions());
    }
}
