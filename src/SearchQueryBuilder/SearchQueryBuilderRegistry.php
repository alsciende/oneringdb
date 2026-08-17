<?php

declare(strict_types=1);

namespace App\SearchQueryBuilder;

use App\Search\Operand;
use App\SearchQueryBuilder\Builder\SearchQueryBuilderInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

/**
 * Provides a registry to get SearchQueryBuilderInterface services for an Operand.
 */
#[AsAlias('app.search_query_builder_registry')]
class SearchQueryBuilderRegistry
{
    /**
     * @var array<SearchQueryBuilderInterface>
     */
    private array $services = [];

    /**
     * @param iterable<SearchQueryBuilderInterface> $services
     */
    public function __construct(
        #[AutowireLocator('app.search_query_builder')]
        iterable $services,
    ) {
        foreach ($services as $service) {
            if ($service instanceof SearchQueryBuilderInterface) {
                $name = $service->getName()->value;

                if (array_key_exists($name, $this->services)) {
                    $duplicate = $this->services[$name];

                    throw new \LogicException(sprintf('Service name "%s" duplicate between %s and %s.', $name, $duplicate::class, $service::class));
                }
                $this->services[$name] = $service;
            }
        }
    }

    /**
     * @return array<string>
     */
    public function getNames(): array
    {
        return array_keys($this->services);
    }

    /**
     * @return array<SearchQueryBuilderInterface>
     */
    public function getSearchQueryBuilders(): array
    {
        return $this->services;
    }

    public function getSearchQueryBuilder(Operand $name): SearchQueryBuilderInterface
    {
        if (array_key_exists($name->value, $this->services)) {
            return $this->services[$name->value];
        }

        throw new \UnexpectedValueException(sprintf('Cannot find service "%s". Available services are %s.', $name->value, implode(',', $this->getNames())));
    }
}
