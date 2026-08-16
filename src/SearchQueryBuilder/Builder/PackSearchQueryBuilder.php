<?php

declare(strict_types=1);

namespace App\SearchQueryBuilder\Builder;

use App\Exception\BadOperatorException;
use App\Search\Operand;
use App\Search\Operator;
use Doctrine\ORM\QueryBuilder;

class PackSearchQueryBuilder extends AbstractSearchQueryBuilder
{
    #[\Override]
    public function getName(): Operand
    {
        return Operand::Pack;
    }

    #[\Override]
    protected function buildQuery(QueryBuilder $queryBuilder, Operand $operand, Operator $operator, string $value, string $identifier): void
    {
        if (! in_array($operator, [Operator::EQ, Operator::NE], true)) {
            throw new BadOperatorException($operator->value, $this->getName());
        }

        if ($operator === Operator::EQ) {
            $queryBuilder->andWhere("(p.id = :{$identifier} or p.shorthand = :{$identifier})");
        } else {
            $queryBuilder->andWhere("(p.id != :{$identifier} and p.shorthand != :{$identifier})");
        }

        $queryBuilder->setParameter($identifier, $value);
    }
}
