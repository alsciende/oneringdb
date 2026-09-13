<?php

declare(strict_types=1);

namespace App\SearchQueryBuilder\Builder;

use App\Enum\Subtype;
use App\Exception\BadValueException;
use App\Search\Operand;
use App\Search\Operator;
use Doctrine\ORM\QueryBuilder;

class SubtypeSearchQueryBuilder extends AbstractSearchQueryBuilder
{
    #[\Override]
    public function getName(): Operand
    {
        return Operand::Subtype;
    }

    #[\Override]
    protected function buildQuery(QueryBuilder $queryBuilder, Operand $operand, Operator $operator, string $value, string $identifier): void
    {
        $subtype = $this->findSubtype($value);
        if ($subtype === null) {
            throw new BadValueException($value, $this->getName());
        }

        $queryBuilder
            ->andWhere("c.subtype {$this->getOperator($operator)} :{$identifier}")
            ->setParameter($identifier, $subtype);
    }

    private function findSubtype(string $value): ?Subtype
    {
        foreach (Subtype::cases() as $subtype) {
            if (strcasecmp($subtype->value, $value) === 0) {
                return $subtype;
            }
        }

        return null;
    }
}
