<?php

declare(strict_types=1);

namespace App\SearchQueryBuilder\Builder;

use App\Entity\Card;
use App\Enum\Type;
use App\Exception\BadOperatorException;
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

        $not = match ($operator) {
            Operator::EQ => '',
            Operator::NE => 'NOT ',
            default => throw new BadOperatorException($operator->value, $this->getName()),
        };

        $queryBuilder->andWhere("c {$not}INSTANCE OF " . Card::TYPE_ENTITY_CLASSES[$type->value]);
    }
}
