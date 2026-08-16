<?php

declare(strict_types=1);

namespace App\SearchQueryBuilder\Builder;

use App\Enum\Type;
use App\Exception\BadValueException;
use App\Search\Operand;
use App\Search\Operator;
use Doctrine\ORM\QueryBuilder;

class TypeSearchQueryBuilder extends AbstractSearchQueryBuilder
{
    #[\Override]
    public function getName(): Operand
    {
        return Operand::Type;
    }

    #[\Override]
    protected function buildQuery(QueryBuilder $queryBuilder, Operand $operand, Operator $operator, string $value, string $identifier): void
    {
        $type = Type::tryFrom($value);
        if ($type === null) {
            throw new BadValueException($value, $this->getName());
        }

        $queryBuilder
            ->andWhere("c.type {$this->getOperator($operator)} :{$identifier}")
            ->setParameter($identifier, $type);
    }
}
