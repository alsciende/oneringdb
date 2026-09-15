<?php

declare(strict_types=1);

namespace App\Tests\SearchQueryBuilder\Builder;

use App\Enum\Subtype;
use App\Exception\BadValueException;
use App\Search\CardCondition;
use App\Search\Operand;
use App\Search\Operator;
use App\SearchQueryBuilder\Builder\SubtypeSearchQueryBuilder;
use App\SearchQueryBuilder\UniqueIdentifierGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SubtypeSearchQueryBuilder::class)]
class SubtypeSearchQueryBuilderTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame(Operand::Subtype, new SubtypeSearchQueryBuilder()->getName());
    }

    public function testHandleWithACaseInsensitiveMatch(): void
    {
        $builder = new SubtypeSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $builder->handle(new UniqueIdentifierGenerator(), $qb, new CardCondition('elf', Operand::Subtype, Operator::EQ));

        $this->assertStringContainsString('c.subtype = :Subtype0', (string) $qb->getDQLPart('where'));
        $this->assertSame(Subtype::Elf, $qb->getParameter('Subtype0')?->getValue());
    }

    public function testHandleWithAnInvalidSubtypeThrows(): void
    {
        $builder = new SubtypeSearchQueryBuilder();
        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $this->expectException(BadValueException::class);
        $builder->handle(new UniqueIdentifierGenerator(), $qb, new CardCondition('xxxx', Operand::Subtype, Operator::EQ));
    }
}
