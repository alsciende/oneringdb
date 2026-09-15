<?php

declare(strict_types=1);

namespace App\Tests\SearchQueryBuilder\Builder;

use App\Exception\BadOperatorException;
use App\Search\CardCondition;
use App\Search\Operand;
use App\Search\Operator;
use App\SearchQueryBuilder\Builder\TextSearchQueryBuilder;
use App\SearchQueryBuilder\UniqueIdentifierGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextSearchQueryBuilder::class)]
class TextSearchQueryBuilderTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame(Operand::Text, new TextSearchQueryBuilder()->getName());
    }

    public function testHandleAddsALikeCondition(): void
    {
        $builder = new TextSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $builder->handle(new UniqueIdentifierGenerator(), $qb, new CardCondition('pipeweed', Operand::Text, Operator::EQ));

        $where = (string) $qb->getDQLPart('where');
        $this->assertStringContainsString('LOWER(c.text) LIKE LOWER(:Text0)', $where);
        $this->assertSame('%pipeweed%', $qb->getParameter('Text0')?->getValue());
    }

    public function testHandleWithAnUnsupportedOperatorThrows(): void
    {
        $builder = new TextSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $this->expectException(BadOperatorException::class);
        $builder->handle(new UniqueIdentifierGenerator(), $qb, new CardCondition('pipeweed', Operand::Text, Operator::LT));
    }
}
