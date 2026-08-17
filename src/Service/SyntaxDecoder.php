<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\BadOperandException;
use App\Exception\BadOperatorException;
use App\Exception\SyntaxException;
use App\Search\CardCondition;
use App\Search\Operand;
use App\Search\Operator;
use App\Search\SearchConditions;
use Psr\Log\LoggerInterface;

/**
 * Decodes from query strings.
 */
readonly class SyntaxDecoder
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Decode a query part into a CardCondition.
     *
     * @throws SyntaxException
     */
    private function parseCondition(string $condition): CardCondition
    {
        $operandValue = substr($condition, 0, 1);
        $operatorValue = substr($condition, 1, 1);
        $value = substr($condition, 2, strlen($condition) - 2);

        // Unquote value if needed
        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $value = substr($value, 1, -1);
        }

        /* @TODO: Separate values with '|' */

        try {
            $operand = Operand::from($operandValue);
        } catch (\ValueError) {
            throw new BadOperandException($operandValue);
        }

        try {
            $operator = Operator::from($operatorValue);
        } catch (\ValueError) {
            throw new BadOperatorException($operatorValue, $operand);
        }

        return new CardCondition($value, $operand, $operator);
    }

    /**
     * Decodes a query string into a SearchConditions which contains all the query elements.
     *
     * @TODO 1st match_all (?:(?<operand>\w)(?<operator>[:!<>]))?(?<value>(?:\w+|(?:"[^"]+"))(?:\|(?:\w+|(?:"[^"]+")))*)
     * @TODO 2nd match_all on each 'value' : (\w+)|(?:"([^"]+)")
     *
     * @param string $query The expression in the form of advanced syntax to evaluate
     */
    public function decode(string $query): SearchConditions
    {
        static $operators = implode('',
            array_map(fn (Operator $operator): string => $operator->value, Operator::cases())
        );

        // The regex pattern
        static $quotedString = '"(?:[^"\\\\]|\\\\.)*"';
        static $pattern = "/
            (\w[{$operators}]{$quotedString})     # char + operator + quoted string
            |
            (\w[{$operators}][\w-]+)               # char + operator + word
            |
            ({$quotedString})                   # standalone quoted string
            |
            ([\w-]+)                             # standalone word
        /x";

        // Match all conditions
        preg_match_all($pattern, $query, $matches);

        // Process the matches
        $conditions = [];
        foreach ($matches[0] as $i => $match) {
            try {
                $conditions[] = match (false) {
                    empty($matches[1][$i]) => $this->parseCondition($matches[1][$i]),
                    empty($matches[2][$i]) => $this->parseCondition($matches[2][$i]),
                    empty($matches[3][$i]) => $this->parseCondition('_:' . $matches[3][$i]),
                    empty($matches[4][$i]) => $this->parseCondition('_:' . $matches[4][$i]),
                    false => throw new \LogicException('Empty query match!'),
                };
            } catch (BadOperandException $e) {
                $this->logger->error("\"{$e->value}\" is not a valid search condition.");
            }
        }

        return SearchConditions::withConditions($conditions);
    }
}
