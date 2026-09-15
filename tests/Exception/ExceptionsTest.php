<?php

declare(strict_types=1);

namespace App\Tests\Exception;

use App\Exception\BadOperandException;
use App\Exception\BadOperatorException;
use App\Exception\BadValueException;
use App\Exception\SyntaxException;
use App\Search\Operand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BadOperandException::class)]
#[CoversClass(BadOperatorException::class)]
#[CoversClass(BadValueException::class)]
#[CoversClass(SyntaxException::class)]
class ExceptionsTest extends TestCase
{
    public function testSyntaxExceptionIsARuntimeException(): void
    {
        $this->assertInstanceOf(\RuntimeException::class, new SyntaxException());
    }

    public function testBadOperandExceptionCarriesTheOffendingValue(): void
    {
        $exception = new BadOperandException('z');

        $this->assertInstanceOf(SyntaxException::class, $exception);
        $this->assertSame('z', $exception->value);
    }

    public function testBadOperatorExceptionCarriesTheOffendingValueAndOperand(): void
    {
        $exception = new BadOperatorException('~', Operand::Title);

        $this->assertInstanceOf(SyntaxException::class, $exception);
        $this->assertSame('~', $exception->value);
        $this->assertSame(Operand::Title, $exception->operand);
    }

    public function testBadValueExceptionCarriesTheOffendingValueAndOperand(): void
    {
        $exception = new BadValueException('xxxx', Operand::Culture);

        $this->assertInstanceOf(SyntaxException::class, $exception);
        $this->assertSame('xxxx', $exception->value);
        $this->assertSame(Operand::Culture, $exception->operand);
    }
}
