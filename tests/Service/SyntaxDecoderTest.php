<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Exception\BadOperatorException;
use App\Search\CardCondition;
use App\Search\Operand;
use App\Search\Operator;
use App\Service\SyntaxDecoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

#[CoversClass(SyntaxDecoder::class)]
class SyntaxDecoderTest extends TestCase
{
    /**
     * @return array<array{string, CardCondition}>
     */
    public static function simpleCardSearchProvider(): array
    {
        return [
            ['rosie', new CardCondition('rosie', Operand::Title, Operator::EQ)],
            ['_:rosie', new CardCondition('rosie', Operand::Title, Operator::EQ)],
            ['"rosie cotton"', new CardCondition('rosie cotton', Operand::Title, Operator::EQ)],
            ['c:shire', new CardCondition('shire', Operand::Culture, Operator::EQ)],
            ['t:ally', new CardCondition('ally', Operand::Type, Operator::EQ)],
            ['o:4', new CardCondition('4', Operand::TwilightCost, Operator::EQ)],
            ['x:spot-a-hobbit', new CardCondition('spot-a-hobbit', Operand::Text, Operator::EQ)],
            ['x:"spot a hobbit"', new CardCondition('spot a hobbit', Operand::Text, Operator::EQ)],
            ['p:01', new CardCondition('01', Operand::Pack, Operator::EQ)],
            ['l:this-is-a-test', new CardCondition('this-is-a-test', Operand::Lore, Operator::EQ)],
            ['l:"this is a test"', new CardCondition('this is a test', Operand::Lore, Operator::EQ)],
        ];
    }

    /**
     * @return array<array{string, array<CardCondition>}>
     */
    public static function complexCardSearchProvider(): array
    {
        return [
            [
                'rosie cotton',
                [
                    new CardCondition('rosie', Operand::Title, Operator::EQ),
                    new CardCondition('cotton', Operand::Title, Operator::EQ),
                ],
            ],
            [
                '_:rosie c:shire',
                [
                    new CardCondition('rosie', Operand::Title, Operator::EQ),
                    new CardCondition('shire', Operand::Culture, Operator::EQ),
                ],
            ],
            [
                '_:rosie _!cotton',
                [
                    new CardCondition('rosie', Operand::Title, Operator::EQ),
                    new CardCondition('cotton', Operand::Title, Operator::NE),
                ],
            ],
            [
                '_:rosie _:cotton',
                [
                    new CardCondition('rosie', Operand::Title, Operator::EQ),
                    new CardCondition('cotton', Operand::Title, Operator::EQ),
                ],
            ],
        ];
    }

    #[DataProvider('simpleCardSearchProvider')]
    public function testSimpleCardSearch(string $query, CardCondition $cardCondition): void
    {
        $decoder = new SyntaxDecoder(new NullLogger());
        $searchConditions = $decoder->decode($query);

        $this->assertEquals(
            [$cardCondition],
            $searchConditions->getConditions(),
        );
    }

    /**
     * @param array<CardCondition> $cardConditions
     */
    #[DataProvider('complexCardSearchProvider')]
    public function testComplexCardSearch(string $query, array $cardConditions): void
    {
        $decoder = new SyntaxDecoder(new NullLogger());
        $searchConditions = $decoder->decode($query);

        $this->assertEquals(
            $cardConditions,
            $searchConditions->getConditions(),
        );
    }

    public function testMultiSearchWithOperatorAndDefault(): void
    {
        $decoder = new SyntaxDecoder(new NullLogger());
        $search = $decoder->decode('rosie cotton');
        $search2 = $decoder->decode('_:rosie cotton');
        $search3 = $decoder->decode('rosie _:cotton');
        $search4 = $decoder->decode('_:rosie _:cotton');

        $this->assertEquals($search, $search2, 'Imp Imp vs Exp Imp');
        $this->assertEquals($search, $search3, 'Imp Imp vs Imp Exp');
        $this->assertEquals($search, $search4, 'Imp Imp vs Exp Exp');
    }

    public function testAnInvalidOperandIsLoggedAndSkipped(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('"z" is not a valid search condition'));

        $decoder = new SyntaxDecoder($logger);
        $searchConditions = $decoder->decode('z:foo');

        $this->assertSame([], $searchConditions->getConditions());
    }

    public function testParseConditionThrowsOnAnInvalidOperator(): void
    {
        $decoder = new SyntaxDecoder(new NullLogger());

        $method = new \ReflectionMethod($decoder, 'parseCondition');

        $this->expectException(BadOperatorException::class);
        $method->invoke($decoder, '_~foo');
    }
}
