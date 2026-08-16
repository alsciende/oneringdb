<?php

declare(strict_types=1);

namespace App\SearchQueryBuilder\Builder;

use App\Exception\BadOperatorException;
use App\Search\CardCondition;
use App\Search\Operand;
use App\Search\Operator;
use App\SearchQueryBuilder\UniqueIdentifierGenerator;
use Doctrine\ORM\QueryBuilder;

/**
 * Provides the base logic for SearchQueryBuilderInterface classes.
 */
abstract class AbstractSearchQueryBuilder implements SearchQueryBuilderInterface
{
    #[\Override]
    public function handle(UniqueIdentifierGenerator $generator, QueryBuilder $queryBuilder, CardCondition $cardCondition): void
    {
        if ($cardCondition->getOperand() !== $this->getName()) {
            throw new \LogicException('Unexpected operand');
        }

        $this->buildQuery(
            $queryBuilder,
            $cardCondition->getOperand(),
            $cardCondition->getOperator(),
            $cardCondition->getValue(),
            $generator->generateIdentifier($this->getName()->name),
        );
    }

    protected function getOperator(Operator $operator, bool $numeric = false, bool $like = false): string
    {
        if ($numeric) {
            return match ($operator) {
                Operator::EQ => '=',
                Operator::NE => '!=',
                Operator::LT => '<',
                Operator::GT => '>',
            };
        }

        if ($like) {
            return match ($operator) {
                Operator::EQ => 'LIKE',
                Operator::NE => 'NOT LIKE',
                default => throw new BadOperatorException($operator->value, $this->getName()),
            };
        }

        return match ($operator) {
            Operator::EQ => '=',
            Operator::NE => '!=',
            default => throw new BadOperatorException($operator->value, $this->getName()),
        };
    }

    #[\Override]
    abstract public function getName(): Operand;

    abstract protected function buildQuery(QueryBuilder $queryBuilder, Operand $operand, Operator $operator, string $value, string $identifier): void;
}
