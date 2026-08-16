<?php

declare(strict_types=1);

namespace App\SearchQueryBuilder\Builder;

use App\Enum\Culture;
use App\Exception\BadValueException;
use App\Search\Operand;
use App\Search\Operator;
use Doctrine\ORM\QueryBuilder;

class CultureSearchQueryBuilder extends AbstractSearchQueryBuilder
{
    #[\Override]
    public function getName(): Operand
    {
        return Operand::Culture;
    }

    #[\Override]
    protected function buildQuery(QueryBuilder $queryBuilder, Operand $operand, Operator $operator, string $value, string $identifier): void
    {
        $culture = Culture::tryFrom($value);
        if ($culture === null) {
            if (array_key_exists($value, Culture::SHORTHANDS)) {
                $culture = Culture::SHORTHANDS[$value];
            } else {
                throw new BadValueException($value, $this->getName());
            }
        }

        $queryBuilder
            ->andWhere("c.culture {$this->getOperator($operator)} :{$identifier}")
            ->setParameter($identifier, $culture);
    }
}
