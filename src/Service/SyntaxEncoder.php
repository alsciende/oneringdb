<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Pack;
use App\Enum\Culture;
use App\Enum\Type;
use App\Search\AdvancedCardSearch;
use App\Search\Operand;

/**
 * Encodes search data into query strings.
 */
class SyntaxEncoder
{
    private function formatValue(string $value): string
    {
        return preg_match('/[^\w-]/', $value) ? '"' . $value . '"' : $value;
    }

    public function encode(AdvancedCardSearch $search): string
    {
        $queryParts = [];

        if (is_string($search->getName())) {
            $queryParts[] = sprintf('%s', $this->formatValue($search->getName()));
        }

        if ($search->getCulture() instanceof Culture) {
            $value = $search->getCulture()->value;
            foreach (Culture::SHORTHANDS as $shorthand => $culture) {
                if ($culture === $search->getCulture()) {
                    $value = $shorthand;
                }
            }

            $queryParts[] = sprintf('%s:%s', Operand::Culture->value, $value);
        }

        if (is_int($search->getTwilightCost())) {
            $queryParts[] = sprintf('%s:%d', Operand::TwilightCost->value, $search->getTwilightCost());
        }

        if ($search->getType() instanceof Type) {
            $queryParts[] = sprintf('%s:%s', Operand::Type->value, $search->getType()->value);
        }

        if (is_string($search->getText())) {
            $queryParts[] = sprintf('%s:%s', Operand::Text->value, $this->formatValue($search->getText()));
        }

        if ($search->getPack() instanceof Pack) {
            $queryParts[] = sprintf('%s:%s', Operand::Pack->value, $search->getPack()->getId());
        }

        return implode(' ', $queryParts);
    }
}
