<?php

declare(strict_types=1);

namespace App\Tests\SearchQueryBuilder\Builder;

use App\Exception\BadValueException;
use App\Search\CardCondition;
use App\Search\Operand;
use App\Search\Operator;
use App\SearchQueryBuilder\Builder\TwilightCostSearchQueryBuilder;
use App\SearchQueryBuilder\UniqueIdentifierGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TwilightCostSearchQueryBuilder::class)]
class TwilightCostSearchQueryBuilderTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame(Operand::TwilightCost, new TwilightCostSearchQueryBuilder()->getName());
    }

    /**
     * @return array<array{Operator, string}>
     */
    public static function operatorProvider(): array
    {
        return [
            [Operator::EQ, '='],
            [Operator::NE, '!='],
            [Operator::LT, '<'],
            [Operator::GT, '>'],
        ];
    }

    #[DataProvider('operatorProvider')]
    public function testHandleMapsEachOperatorToItsSqlEquivalent(Operator $operator, string $sql): void
    {
        $builder = new TwilightCostSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $builder->handle(new UniqueIdentifierGenerator(), $qb, new CardCondition('4', Operand::TwilightCost, $operator));

        $this->assertStringContainsString("c.twilightCost {$sql} :TwilightCost0", (string) $qb->getDQLPart('where'));
        $this->assertSame(4, $qb->getParameter('TwilightCost0')?->getValue());
    }

    public function testHandleWithANonNumericValueThrows(): void
    {
        $builder = new TwilightCostSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $this->expectException(BadValueException::class);
        $builder->handle(new UniqueIdentifierGenerator(), $qb, new CardCondition('xxxx', Operand::TwilightCost, Operator::EQ));
    }
}
