<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\PublishedSetRepository;

/**
 * Provides data about PublishedSets.
 */
class PublishedSetService
{
    public function __construct(
        private readonly PublishedSetRepository $repository,
    ) {
    }

    /**
     * Return an array of id => name for all sets.
     *
     * @return array<string,string>
     */
    public function all(): array
    {
        $publishedSets = [];

        foreach ($this->repository->findBy([], [
            'id' => 'ASC',
        ]) as $publishedSet) {
            $publishedSets[$publishedSet->getId()] = $publishedSet->getName();
        }

        return $publishedSets;
    }
}
