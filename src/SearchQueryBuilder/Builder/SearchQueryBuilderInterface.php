<?php

declare(strict_types=1);

namespace App\SearchQueryBuilder\Builder;

use App\Search\CardCondition;
use App\Search\Operand;
use App\SearchQueryBuilder\UniqueIdentifierGenerator;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Interface for SearchQueryBuilders injected into SearchQueryBuilderRegistry.
 */
#[AutoconfigureTag('app.search_query_builder')]
interface SearchQueryBuilderInterface
{
    public function getName(): Operand;

    public function handle(UniqueIdentifierGenerator $generator, QueryBuilder $queryBuilder, CardCondition $cardCondition): void;
}
