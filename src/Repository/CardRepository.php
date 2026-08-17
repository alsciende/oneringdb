<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Card;
use App\Entity\Pack;
use App\Entity\PackCard;
use App\SearchQueryBuilder\SearchQueryBuilder;
use App\Service\SyntaxDecoder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
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
            $queryBuilder->orderBy('p.id')->addOrderBy('pc.position');
        } else {
            $queryBuilder->orderBy("c.{$sort}");
        }

        return $queryBuilder->setCacheable(false);
    }

    /**
     * @return array<Card>
     */
    public function findByPack(Pack $pack): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('c')->from(Card::class, 'c')
            ->join(PackCard::class, 'pc', Join::WITH, 'pc.pack = :pack')
            ->setParameter('pack', $pack);

        return $qb->getQuery()->setCacheable(true)->getResult();
    }

    /**
     * Used by CardController.
     */
    public function getCard(string $id): ?Card
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.packCards', 'pc')
            ->addSelect('pc')
            ->leftJoin('pc.pack', 'p')
            ->addSelect('p')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->setCacheable(true)
            ->getOneOrNullResult();
    }
}
