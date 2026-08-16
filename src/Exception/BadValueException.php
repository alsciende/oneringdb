<?php

declare(strict_types=1);

namespace App\Exception;

use App\Search\Operand;

class BadValueException extends SyntaxException
{
    public function __construct(
        public string $value,
        public Operand $operand,
    ) {
        parent::__construct();
    }
}
