<?php

declare(strict_types=1);

namespace App\SearchQueryBuilder\Builder;

use App\Search\Operand;
use App\Search\Operator;
use Doctrine\ORM\QueryBuilder;

class TextSearchQueryBuilder extends AbstractSearchQueryBuilder
{
    #[\Override]
    public function getName(): Operand
    {
        return Operand::Text;
    }

    #[\Override]
    protected function buildQuery(QueryBuilder $queryBuilder, Operand $operand, Operator $operator, string $value, string $identifier): void
    {
        $queryBuilder
            ->andWhere("LOWER(c.text) {$this->getOperator($operator, like: true)} LOWER(:{$identifier})")
            ->setParameter($identifier, "%{$value}%");
    }
}
