<?php

declare(strict_types=1);

namespace App\SearchQueryBuilder;

/**
 * Generates unique identifiers to prevent overlapping in a given QueryBuilder.
 */
class UniqueIdentifierGenerator
{
    /**
     * @var array<string, int>
     */
    private array $identifiers = [];

    public function generateIdentifier(string $name): string
    {
        $count = $this->identifiers[$name] ?? 0;
        $this->identifiers[$name] = $count + 1;

        return $name . $count;
    }
}
