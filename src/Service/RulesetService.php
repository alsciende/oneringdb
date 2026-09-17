<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Card;
use App\Entity\Ruleset;
use App\Repository\CardRepository;
use App\Repository\PublishedSetRepository;
use App\Repository\RulesetRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Provides validation and creation logic for Rulesets.
 */
class RulesetService
{
    public function __construct(
        private readonly PublishedSetRepository $publishedSetRepository,
        private readonly CardRepository $cardRepository,
        private readonly RulesetRepository $rulesetRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Creates a new Ruleset and links it to every Card of a base Ruleset (the active one by
     * default, or the given one).
     *
     * The card_ruleset rows are copied with a single bulk SQL statement rather than one
     * addCard() per Card: with ~1839 Cards, going through the ORM would touch (and so write to
     * the L2C cache for) every one of their $rulesets collections individually. The bulk insert
     * bypasses the ORM/L2C entirely, so the now-stale Card::$rulesets cache entries are evicted
     * (a single, cheap region-wide clear) instead of updated one by one.
     */
    public function createRuleset(string $name, ?Ruleset $baseRuleset = null): Ruleset
    {
        $baseRuleset ??= $this->rulesetRepository->findOneBy([
            'isActive' => true,
        ]) ?? throw new \RuntimeException('Cannot find an active Ruleset');

        $ruleset = new Ruleset()->setName($name);
        $this->entityManager->persist($ruleset);
        $this->entityManager->flush();

        $this->entityManager->getConnection()->executeStatement(
            'INSERT INTO card_ruleset (card_id, ruleset_id) SELECT card_id, ? FROM card_ruleset WHERE ruleset_id = ?',
            [$ruleset->getId(), $baseRuleset->getId()],
        );

        $this->entityManager->getCache()?->evictCollectionRegion(Card::class, 'rulesets');
        $this->entityManager->refresh($ruleset);

        return $ruleset;
    }

    /**
     * Checks that the given Ruleset provides a Card for every position known to exist in each
     * PublishedSet (i.e. every position ever assigned to a Card of that set, across all Rulesets).
     *
     * @return array<string, list<int>> Map of PublishedSet id to its positions missing a Card in
     *                                  the given Ruleset. Empty when the Ruleset is complete.
     */
    public function findMissingPositions(Ruleset $ruleset): array
    {
        $missingPositionsByPublishedSet = [];

        foreach ($this->publishedSetRepository->findAll() as $publishedSet) {
            $knownPositions = $this->cardRepository->findDistinctPositions($publishedSet, null);
            $rulesetPositions = $this->cardRepository->findDistinctPositions($publishedSet, $ruleset);

            $missingPositions = array_values(array_diff($knownPositions, $rulesetPositions));
            sort($missingPositions);

            if ($missingPositions !== []) {
                $missingPositionsByPublishedSet[$publishedSet->getId()] = $missingPositions;
            }
        }

        return $missingPositionsByPublishedSet;
    }

    public function isComplete(Ruleset $ruleset): bool
    {
        return $this->findMissingPositions($ruleset) === [];
    }

    /**
     * Replaces $oldCard with $newCard in the given Ruleset, within a single DB transaction.
     */
    public function swapCard(Ruleset $ruleset, Card $oldCard, Card $newCard): void
    {
        if (! $ruleset->getCards()->contains($oldCard)) {
            throw new \RuntimeException(sprintf('Card "%s" is not linked to Ruleset "%s"', $oldCard->getId(), $ruleset->getName()));
        }

        $this->entityManager->wrapInTransaction(function () use ($ruleset, $oldCard, $newCard): void {
            $ruleset->removeCard($oldCard);
            $ruleset->addCard($newCard);
        });
    }

    /**
     * Makes the given Ruleset the active one, provided it is complete. The previously active
     * Ruleset (if any) is deactivated first, in the same DB transaction, so the unique constraint
     * on `ruleset.is_active` is never violated.
     */
    public function activate(Ruleset $ruleset): void
    {
        if (! $this->isComplete($ruleset)) {
            throw new \RuntimeException(sprintf('Ruleset "%s" is not complete and cannot be activated', $ruleset->getName()));
        }

        $this->entityManager->wrapInTransaction(function () use ($ruleset): void {
            $activeRuleset = $this->rulesetRepository->findOneBy([
                'isActive' => true,
            ]);

            if ($activeRuleset !== null && $activeRuleset !== $ruleset) {
                $activeRuleset->setActive(null);
                $this->entityManager->flush();
            }

            $ruleset->setActive(true);
            $this->entityManager->flush();
        });
    }

    /**
     * The Rulesets under which a Card (in any of its revisions) changed, oldest first, each paired
     * with the revision that became active under it. Consecutive Rulesets pointing to the same
     * revision are collapsed to the first one, so only actual changes are listed. Used by
     * CardController to build the revision history table on a Card's page.
     *
     * @return list<array{card: Card, ruleset: Ruleset}>
     */
    public function findRulesetHistory(Card $card): array
    {
        $history = [];

        foreach ($this->cardRepository->findRevisions($card) as $revision) {
            foreach ($revision->getRulesets() as $ruleset) {
                $history[] = [
                    'card' => $revision,
                    'ruleset' => $ruleset,
                ];
            }
        }

        usort($history, static fn (array $a, array $b): int => $a['ruleset']->getPublicationDate() <=> $b['ruleset']->getPublicationDate());

        $changes = [];
        $previousCard = null;
        foreach ($history as $row) {
            if ($row['card'] !== $previousCard) {
                $changes[] = $row;
            }

            $previousCard = $row['card'];
        }

        return $changes;
    }

    /**
     * A Ruleset must only ever point to one revision of a given Card (same PublishedSet +
     * position). Used by CardCrudController before saving a Card whose $rulesets association was
     * edited directly through the EasyAdmin form (bypassing swapCard()'s atomic swap): for each
     * Ruleset now linked to $card, that Ruleset is detached from every other revision of the same
     * Card that held it before.
     */
    public function detachOtherRevisionsFromRulesets(Card $card): void
    {
        foreach ($card->getRulesets() as $ruleset) {
            foreach ($this->cardRepository->findRevisions($card) as $otherRevision) {
                if ($otherRevision !== $card) {
                    $otherRevision->removeRuleset($ruleset);
                }
            }
        }
    }
}
