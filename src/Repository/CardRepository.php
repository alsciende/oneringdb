<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Card;
use App\Entity\PublishedSet;
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
     * Used by CardController.
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
}
