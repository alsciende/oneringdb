<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Card;
use App\Entity\PublishedSet;
use App\Entity\Ruleset;
use App\SearchQueryBuilder\SearchQueryBuilder;
use App\Service\SyntaxDecoder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
class CardRepository extends ServiceEntityRepository
{
    public const int PAGE_SIZE = 60;

    public function __construct(
        ManagerRegistry $registry,
        private readonly SyntaxDecoder $syntaxDecoder,
        private readonly SearchQueryBuilder $searchQueryBuilder,
    ) {
        parent::__construct($registry, Card::class);
    }

    /**
     * @return bool Given an id, return true if the corresponding Card object exists, false otherwise
     */
    public function exists(Card $card): bool
    {
        return $this->find($card->getId()) !== null;
    }

    public function search(string $query, string $sort = 'title'): QueryBuilder
    {
        $searchConditions = $this->syntaxDecoder->decode($query);
        $queryBuilder = $this->searchQueryBuilder->buildQuery($searchConditions);

        if ($sort === 'position') {
            $queryBuilder->orderBy('p.id')->addOrderBy('c.position');
        } else {
            $queryBuilder->orderBy("c.{$sort}");
        }

        return $queryBuilder->setCacheable(false);
    }

    /**
     * @return array<Card>
     */
    public function findByPublishedSet(PublishedSet $publishedSet): array
    {
        return $this->findBy([
            'publishedSet' => $publishedSet,
        ]);
    }

    /**
     * Distinct positions used by cards of a PublishedSet. When a Ruleset is given, only positions
     * covered by a Card linked to that Ruleset are returned; otherwise every position ever assigned
     * to a Card of that set (across all Rulesets) is returned.
     *
     * @return list<int>
     */
    public function findDistinctPositions(PublishedSet $publishedSet, ?Ruleset $ruleset = null): array
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->select('DISTINCT c.position')
            ->where('c.publishedSet = :publishedSet')
            ->andWhere('c.position IS NOT NULL')
            ->setParameter('publishedSet', $publishedSet);

        if ($ruleset !== null) {
            $queryBuilder
                ->innerJoin('c.rulesets', 'r')
                ->andWhere('r = :ruleset')
                ->setParameter('ruleset', $ruleset);
        }

        return array_values(array_map(intval(...), $queryBuilder->getQuery()->getSingleColumnResult()));
    }

    /**
     * Used by CardController. Not filtered by the active Ruleset: a Card page can be opened by id
     * regardless of whether that revision is currently active (e.g. a superseded historical
     * revision reached via an old link).
     */
    public function getCard(string $id): ?Card
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.publishedSet', 'p')
            ->addSelect('p')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->setCacheable(true)
            ->getOneOrNullResult();
    }

    /**
     * Used by CardController to link to the previous card of the same published set.
     */
    public function findPreviousCard(Card $card): ?Card
    {
        if ($card->getPosition() === null) {
            return null;
        }

        return $this->createQueryBuilder('c')
            ->innerJoin('c.rulesets', 'r')
            ->where('r.isActive = true')
            ->andWhere('c.publishedSet = :publishedSet')
            ->andWhere('c.position < :position')
            ->setParameter('publishedSet', $card->getPublishedSet())
            ->setParameter('position', $card->getPosition())
            ->orderBy('c.position', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->setCacheable(true)
            ->getOneOrNullResult();
    }

    /**
     * Used by CardController to link to the next card of the same published set.
     */
    public function findNextCard(Card $card): ?Card
    {
        if ($card->getPosition() === null) {
            return null;
        }

        return $this->createQueryBuilder('c')
            ->innerJoin('c.rulesets', 'r')
            ->where('r.isActive = true')
            ->andWhere('c.publishedSet = :publishedSet')
            ->andWhere('c.position > :position')
            ->setParameter('publishedSet', $card->getPublishedSet())
            ->setParameter('position', $card->getPosition())
            ->orderBy('c.position', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->setCacheable(true)
            ->getOneOrNullResult();
    }

    /**
     * Every revision of a Card: every Card sharing the same PublishedSet and position, across all
     * Rulesets. Used by CardController to build the revision history table on a Card's page.
     *
     * @return array<Card>
     */
    public function findRevisions(Card $card): array
    {
        if ($card->getPosition() === null) {
            return [$card];
        }

        return $this->createQueryBuilder('c')
            ->where('c.publishedSet = :publishedSet')
            ->andWhere('c.position = :position')
            ->setParameter('publishedSet', $card->getPublishedSet())
            ->setParameter('position', $card->getPosition())
            ->getQuery()
            ->setCacheable(true)
            ->getResult();
    }
}
