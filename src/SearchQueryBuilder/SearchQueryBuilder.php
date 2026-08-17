<?php

declare(strict_types=1);

namespace App\SearchQueryBuilder;

use App\Entity\Card;
use App\Exception\BadOperatorException;
use App\Exception\BadValueException;
use App\Search\SearchConditions;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;

/**
 * Create a Doctrine QueryBuilder from a SearchConditions object.
 */
readonly class SearchQueryBuilder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SearchQueryBuilderRegistry $registry,
        private LoggerInterface $logger,
    ) {
    }

    public function buildQuery(SearchConditions $searchConditions): QueryBuilder
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('c')
            ->from(Card::class, 'c')
            ->leftJoin('c.packCards', 'pc')
            ->addSelect('pc')
            ->leftJoin('pc.pack', 'p')
            ->addSelect('p')
        ;

        $generator = new UniqueIdentifierGenerator();

        foreach ($searchConditions->getConditions() as $condition) {
            $builder = $this->registry->getSearchQueryBuilder($condition->getOperand());
            try {
                $builder->handle($generator, $qb, $condition);
            } catch (BadValueException $e) {
                $this->logger->error("\"{$e->value}\" is not a valid value for a search with \"{$e->operand->value}\".");
            } catch (BadOperatorException $e) {
                $this->logger->error("\"{$e->value}\" is not a valid operator for a search with \"{$e->operand->value}\".");
            }
        }

        return $qb;
    }
}
