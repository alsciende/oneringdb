<?php

declare(strict_types=1);

namespace App\Search;

/**
 * A search condition part of a search query.
 */
readonly class CardCondition
{
    public function __construct(
        private string $value,
        private Operand $operand = Operand::Name,
        private Operator $operator = Operator::EQ,
    ) {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getOperand(): Operand
    {
        return $this->operand;
    }

    public function getOperator(): Operator
    {
        return $this->operator;
    }
}
