<?php

declare(strict_types=1);

namespace App\Exception;

class BadOperandException extends SyntaxException
{
    public function __construct(
        public string $value,
    ) {
        parent::__construct();
    }
}
