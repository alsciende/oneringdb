<?php

declare(strict_types=1);

namespace App\Tests\SearchQueryBuilder;

use App\Exception\BadOperatorException;
use App\Exception\BadValueException;
use App\Search\CardCondition;
use App\Search\Operand;
use App\Search\Operator;
use App\Search\SearchConditions;
use App\SearchQueryBuilder\Builder\CultureSearchQueryBuilder;
use App\SearchQueryBuilder\Builder\SearchQueryBuilderInterface;
use App\SearchQueryBuilder\Builder\TitleSearchQueryBuilder;
use App\SearchQueryBuilder\SearchQueryBuilder;
use App\SearchQueryBuilder\SearchQueryBuilderRegistry;
use App\SearchQueryBuilder\UniqueIdentifierGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(SearchQueryBuilder::class)]
class SearchQueryBuilderTest extends TestCase
{
    private function createEntityManager(): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturnCallback(static fn (): QueryBuilder => new QueryBuilder($em));

        return $em;
    }

    public function testBuildQuerySelectsCardsAndAppliesConditions(): void
    {
        $registry = new SearchQueryBuilderRegistry([
            new TitleSearchQueryBuilder(),
            new CultureSearchQueryBuilder(),
        ]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $builder = new SearchQueryBuilder($this->createEntityManager(), $registry, $logger);
        $searchConditions = SearchConditions::withConditions([
            new CardCondition('rosie', Operand::Title, Operator::EQ),
            new CardCondition('shire', Operand::Culture, Operator::EQ),
        ]);

        $qb = $builder->buildQuery($searchConditions);

        $dql = $qb->getDQL();
        $this->assertStringContainsString('SELECT c, p FROM App\Entity\Card c LEFT JOIN c.publishedSet p', $dql);
        $this->assertStringContainsString('LOWER(c.title) LIKE LOWER(:Title0)', $dql);
        $this->assertStringContainsString('c.culture = :Culture0', $dql);
    }

    public function testBuildQueryLogsAndSkipsOnBadValue(): void
    {
        $failingBuilder = new class implements SearchQueryBuilderInterface {
            public function getName(): Operand
            {
                return Operand::Title;
            }

            public function handle(UniqueIdentifierGenerator $generator, QueryBuilder $queryBuilder, CardCondition $cardCondition): void
            {
                throw new BadValueException($cardCondition->getValue(), $cardCondition->getOperand());
            }
        };

        $registry = new SearchQueryBuilderRegistry([$failingBuilder]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('"xxxx" is not a valid value'));

        $builder = new SearchQueryBuilder($this->createEntityManager(), $registry, $logger);
        $searchConditions = SearchConditions::withConditions([
            new CardCondition('xxxx', Operand::Title, Operator::EQ),
        ]);

        $builder->buildQuery($searchConditions);
    }

    public function testBuildQueryLogsAndSkipsOnBadOperator(): void
    {
        $failingBuilder = new class implements SearchQueryBuilderInterface {
            public function getName(): Operand
            {
                return Operand::Title;
            }

            public function handle(UniqueIdentifierGenerator $generator, QueryBuilder $queryBuilder, CardCondition $cardCondition): void
            {
                throw new BadOperatorException($cardCondition->getOperator()->value, $cardCondition->getOperand());
            }
        };

        $registry = new SearchQueryBuilderRegistry([$failingBuilder]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('is not a valid operator'));

        $builder = new SearchQueryBuilder($this->createEntityManager(), $registry, $logger);
        $searchConditions = SearchConditions::withConditions([
            new CardCondition('xxxx', Operand::Title, Operator::LT),
        ]);

        $builder->buildQuery($searchConditions);
    }
}
