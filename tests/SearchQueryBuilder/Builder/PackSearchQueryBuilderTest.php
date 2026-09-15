<?php

declare(strict_types=1);

namespace App\Tests\SearchQueryBuilder\Builder;

use App\Exception\BadOperatorException;
use App\Search\CardCondition;
use App\Search\Operand;
use App\Search\Operator;
use App\SearchQueryBuilder\Builder\PackSearchQueryBuilder;
use App\SearchQueryBuilder\UniqueIdentifierGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PackSearchQueryBuilder::class)]
class PackSearchQueryBuilderTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame(Operand::Pack, new PackSearchQueryBuilder()->getName());
    }

    public function testHandleWithEquals(): void
    {
        $builder = new PackSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $builder->handle(new UniqueIdentifierGenerator(), $qb, new CardCondition('01', Operand::Pack, Operator::EQ));

        $where = (string) $qb->getDQLPart('where');
        $this->assertStringContainsString('(p.id = :Pack0 or p.shorthand = :Pack0)', $where);
        $this->assertSame('01', $qb->getParameter('Pack0')?->getValue());
    }

    public function testHandleWithNotEquals(): void
    {
        $builder = new PackSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $builder->handle(new UniqueIdentifierGenerator(), $qb, new CardCondition('01', Operand::Pack, Operator::NE));

        $where = (string) $qb->getDQLPart('where');
        $this->assertStringContainsString('(p.id != :Pack0 and p.shorthand != :Pack0)', $where);
    }

    public function testHandleWithAnUnsupportedOperatorThrows(): void
    {
        $builder = new PackSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $this->expectException(BadOperatorException::class);
        $builder->handle(new UniqueIdentifierGenerator(), $qb, new CardCondition('01', Operand::Pack, Operator::LT));
    }
}
