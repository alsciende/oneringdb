<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Search\CardCondition;
use App\Search\Operand;
use App\Search\Operator;
use App\Service\SyntaxDecoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
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
            ['absolution', new CardCondition('absolution', Operand::Name, Operator::EQ)],
            ['_:absolution', new CardCondition('absolution', Operand::Name, Operator::EQ)],
            ['"absolution sphere"', new CardCondition('absolution sphere', Operand::Name, Operator::EQ)],
            ['f:neogenesis-church', new CardCondition('neogenesis-church', Operand::Culture, Operator::EQ)],
            ['t:unit', new CardCondition('unit', Operand::Type, Operator::EQ)],
            ['c:4', new CardCondition('4', Operand::TwilightCost, Operator::EQ)],
            ['x:draw-two-cards', new CardCondition('draw-two-cards', Operand::Text, Operator::EQ)],
            ['x:"draw two cards"', new CardCondition('draw two cards', Operand::Text, Operator::EQ)],
            ['p:starter', new CardCondition('starter', Operand::Pack, Operator::EQ)],
            ['q:4', new CardCondition('4', Operand::Quantity, Operator::EQ)],
            ['n:10', new CardCondition('10', Operand::Position, Operator::EQ)],
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
                'absolution sphere',
                [
                    new CardCondition('absolution', Operand::Name, Operator::EQ),
                    new CardCondition('sphere', Operand::Name, Operator::EQ),
                ],
            ],
            [
                '_:absolution f:neogenesis-church',
                [
                    new CardCondition('absolution', Operand::Name, Operator::EQ),
                    new CardCondition('neogenesis-church', Operand::Culture, Operator::EQ),
                ],
            ],
            [
                '_:absolution _!sphere',
                [
                    new CardCondition('absolution', Operand::Name, Operator::EQ),
                    new CardCondition('sphere', Operand::Name, Operator::NE),
                ],
            ],
            [
                '_:absolution _:sphere',
                [
                    new CardCondition('absolution', Operand::Name, Operator::EQ),
                    new CardCondition('sphere', Operand::Name, Operator::EQ),
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
        $search = $decoder->decode('absolution sphere');
        $search2 = $decoder->decode('_:absolution sphere');
        $search3 = $decoder->decode('absolution _:sphere');
        $search4 = $decoder->decode('_:absolution _:sphere');

        $this->assertEquals($search, $search2, 'Imp Imp vs Exp Imp');
        $this->assertEquals($search, $search3, 'Imp Imp vs Imp Exp');
        $this->assertEquals($search, $search4, 'Imp Imp vs Exp Exp');
    }
}
