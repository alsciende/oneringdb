<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Pack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pack>
 */
class PackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pack::class);
    }

    /**
     * @param array<string, mixed> $criterias
     *
     * @return Pack[] Returns an array of Card objects which have a name that contains the provided string
     */
    public function findByPartialField(array $criterias): array
    {
        $qb = $this->createQueryBuilder('c');

        foreach ($criterias as $key => $value) {
            if (in_array($key, ['name'], true)) {
                $qb->andWhere($qb->expr()->like("LOWER(c.{$key})", "LOWER(:{$key})"))
                    ->setParameter($key, "%{$value}%");
            } else {
                $qb->andWhere($qb->expr()->eq("c.{$key}", ":{$key}"))
                    ->setParameter($key, $value);
            }
        }

        return $qb->getQuery()->getResult();
    }
}
