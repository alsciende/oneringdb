<?php

declare(strict_types=1);

namespace App\Tests\SearchQueryBuilder\Builder;

use App\Search\CardCondition;
use App\Search\Operand;
use App\Search\Operator;
use App\SearchQueryBuilder\Builder\AbstractSearchQueryBuilder;
use App\SearchQueryBuilder\Builder\TitleSearchQueryBuilder;
use App\SearchQueryBuilder\UniqueIdentifierGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractSearchQueryBuilder::class)]
class AbstractSearchQueryBuilderTest extends TestCase
{
    public function testHandleWithAMismatchedOperandThrows(): void
    {
        $builder = new TitleSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unexpected operand');
        $builder->handle(new UniqueIdentifierGenerator(), $qb, new CardCondition('shire', Operand::Culture, Operator::EQ));
    }
}
