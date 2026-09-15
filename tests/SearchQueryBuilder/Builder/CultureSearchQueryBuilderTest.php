<?php

declare(strict_types=1);

namespace App\Tests\SearchQueryBuilder\Builder;

use App\Exception\BadOperatorException;
use App\Exception\BadValueException;
use App\Search\CardCondition;
use App\Search\Operand;
use App\Search\Operator;
use App\SearchQueryBuilder\Builder\CultureSearchQueryBuilder;
use App\SearchQueryBuilder\UniqueIdentifierGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CultureSearchQueryBuilder::class)]
class CultureSearchQueryBuilderTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame(Operand::Culture, new CultureSearchQueryBuilder()->getName());
    }

    public function testHandleWithAFullCultureValue(): void
    {
        $builder = new CultureSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $builder->handle(new UniqueIdentifierGenerator(), $qb, new CardCondition('shire', Operand::Culture, Operator::EQ));

        $this->assertStringContainsString('c.culture = :Culture0', (string) $qb->getDQLPart('where'));
        $this->assertSame(\App\Enum\Culture::Shire, $qb->getParameter('Culture0')?->getValue());
    }

    public function testHandleWithAShorthandCultureValue(): void
    {
        $builder = new CultureSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $builder->handle(new UniqueIdentifierGenerator(), $qb, new CardCondition('sh', Operand::Culture, Operator::NE));

        $this->assertStringContainsString('c.culture != :Culture0', (string) $qb->getDQLPart('where'));
        $this->assertSame(\App\Enum\Culture::Shire, $qb->getParameter('Culture0')?->getValue());
    }

    public function testHandleWithAnInvalidCultureThrows(): void
    {
        $builder = new CultureSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $this->expectException(BadValueException::class);
        $builder->handle(new UniqueIdentifierGenerator(), $qb, new CardCondition('xxxx', Operand::Culture, Operator::EQ));
    }

    public function testHandleWithAnInvalidOperatorThrows(): void
    {
        $builder = new CultureSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $this->expectException(BadOperatorException::class);
        $builder->handle(new UniqueIdentifierGenerator(), $qb, new CardCondition('shire', Operand::Culture, Operator::LT));
    }
}
