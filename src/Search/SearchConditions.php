<?php

declare(strict_types=1);

namespace App\Search;

/**
 * A collection of search conditions decoded from a search query.
 */
class SearchConditions
{
    /**
     * @var array<CardCondition>
     */
    private array $conditions = [];

    /**
     * @param array<CardCondition> $conditions
     */
    public static function withConditions(array $conditions): self
    {
        $search = new self();
        foreach ($conditions as $condition) {
            $search->addCondition($condition);
        }

        return $search;
    }

    public function addCondition(CardCondition $condition): void
    {
        $this->conditions[] = $condition;
    }

    /**
     * @return array<CardCondition>
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }
}
