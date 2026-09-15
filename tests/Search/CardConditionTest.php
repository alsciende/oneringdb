<?php

declare(strict_types=1);

namespace App\Tests\Search;

use App\Search\CardCondition;
use App\Search\Operand;
use App\Search\Operator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CardCondition::class)]
class CardConditionTest extends TestCase
{
    public function testDefaultsToTitleAndEquals(): void
    {
        $condition = new CardCondition('rosie');

        $this->assertSame('rosie', $condition->getValue());
        $this->assertSame(Operand::Title, $condition->getOperand());
        $this->assertSame(Operator::EQ, $condition->getOperator());
    }

    public function testExplicitOperandAndOperator(): void
    {
        $condition = new CardCondition('shire', Operand::Culture, Operator::NE);

        $this->assertSame('shire', $condition->getValue());
        $this->assertSame(Operand::Culture, $condition->getOperand());
        $this->assertSame(Operator::NE, $condition->getOperator());
    }
}
