<?php

declare(strict_types=1);

namespace App\Tests\SearchQueryBuilder\Builder;

use App\Entity\CardTypes\AllyCard;
use App\Exception\BadOperatorException;
use App\Exception\BadValueException;
use App\Search\CardCondition;
use App\Search\Operand;
use App\Search\Operator;
use App\SearchQueryBuilder\Builder\TypeSearchQueryBuilder;
use App\SearchQueryBuilder\UniqueIdentifierGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TypeSearchQueryBuilder::class)]
class TypeSearchQueryBuilderTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame(Operand::Type, new TypeSearchQueryBuilder()->getName());
    }

    public function testHandleWithEqualsAddsAnInstanceOfCondition(): void
    {
        $builder = new TypeSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $builder->handle(new UniqueIdentifierGenerator(), $qb, new CardCondition('ally', Operand::Type, Operator::EQ));

        $where = (string) $qb->getDQLPart('where');
        $this->assertStringContainsString('c INSTANCE OF ' . AllyCard::class, $where);
        $this->assertStringNotContainsString('NOT INSTANCE OF', $where);
    }

    public function testHandleWithNotEqualsNegatesTheInstanceOfCondition(): void
    {
        $builder = new TypeSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $builder->handle(new UniqueIdentifierGenerator(), $qb, new CardCondition('ally', Operand::Type, Operator::NE));

        $this->assertStringContainsString('c NOT INSTANCE OF ' . AllyCard::class, (string) $qb->getDQLPart('where'));
    }

    public function testHandleWithAnInvalidTypeThrows(): void
    {
        $builder = new TypeSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $this->expectException(BadValueException::class);
        $builder->handle(new UniqueIdentifierGenerator(), $qb, new CardCondition('xxxx', Operand::Type, Operator::EQ));
    }

    public function testHandleWithAnUnsupportedOperatorThrows(): void
    {
        $builder = new TypeSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $this->expectException(BadOperatorException::class);
        $builder->handle(new UniqueIdentifierGenerator(), $qb, new CardCondition('ally', Operand::Type, Operator::LT));
    }
}
