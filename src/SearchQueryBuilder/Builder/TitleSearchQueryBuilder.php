<?php

declare(strict_types=1);

namespace App\SearchQueryBuilder\Builder;

use App\Search\Operand;
use App\Search\Operator;
use Doctrine\ORM\QueryBuilder;

class TitleSearchQueryBuilder extends AbstractSearchQueryBuilder
{
    #[\Override]
    public function getName(): Operand
    {
        return Operand::Name;
    }

    #[\Override]
    protected function buildQuery(QueryBuilder $queryBuilder, Operand $operand, Operator $operator, string $value, string $identifier): void
    {
        $queryBuilder
            ->andWhere("LOWER(c.title) {$this->getOperator($operator, like: true)} LOWER(:{$identifier})")
            ->setParameter($identifier, "%{$value}%");
    }
}
