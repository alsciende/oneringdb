<?php

declare(strict_types=1);

namespace App\Tests\SearchQueryBuilder\Builder;

use App\Search\CardCondition;
use App\Search\Operand;
use App\Search\Operator;
use App\SearchQueryBuilder\Builder\TitleSearchQueryBuilder;
use App\SearchQueryBuilder\UniqueIdentifierGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TitleSearchQueryBuilder::class)]
class TitleSearchQueryBuilderTest extends TestCase
{
    public function testGetName(): void
    {
        $builder = new TitleSearchQueryBuilder();

        $this->assertSame(Operand::Title, $builder->getName());
    }

    public function testHandleAddsALikeConditionForEquals(): void
    {
        $builder = new TitleSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));
        $condition = new CardCondition('rosie', Operand::Title, Operator::EQ);

        $builder->handle(new UniqueIdentifierGenerator(), $qb, $condition);

        $where = (string) $qb->getDQLPart('where');
        $this->assertStringContainsString('LOWER(c.title) LIKE LOWER(:Title0)', $where);
        $this->assertSame('%rosie%', $qb->getParameter('Title0')?->getValue());
    }

    public function testHandleAddsANotLikeConditionForNotEquals(): void
    {
        $builder = new TitleSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));
        $condition = new CardCondition('rosie', Operand::Title, Operator::NE);

        $builder->handle(new UniqueIdentifierGenerator(), $qb, $condition);

        $where = (string) $qb->getDQLPart('where');
        $this->assertStringContainsString('LOWER(c.title) NOT LIKE LOWER(:Title0)', $where);
    }
}
