<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Card;
use App\Entity\CardTypes\CompanionCard;
use App\Entity\Ruleset;
use App\Repository\CardRepository;
use App\Repository\PublishedSetRepository;
use App\Repository\RulesetRepository;
use App\Service\RulesetService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(RulesetService::class)]
class RulesetServiceIntegrationTest extends KernelTestCase
{
    private ?int $originallyActiveRulesetId = null;

    /**
     * These tests persist real rows outside of any rolled-back transaction (this project has no
     * transactional test isolation), so clean up everything they create to avoid leaking state
     * into other tests (e.g. published-set card counts, or which Ruleset is active). The
     * originally active Ruleset's id (not its name, which fixtures are free to change) is
     * captured before the test runs so it can be restored afterwards.
     */
    protected function setUp(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->originallyActiveRulesetId = $entityManager->getConnection()->fetchOne('SELECT id FROM ruleset WHERE is_active = true') ?: null;
    }

    protected function tearDown(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();
        $connection->executeStatement("DELETE FROM ruleset WHERE name LIKE 'Errata %'");
        $connection->executeStatement("DELETE FROM card WHERE id = '01001.errata-test'");

        if ($this->originallyActiveRulesetId !== null) {
            $connection->executeStatement('UPDATE ruleset SET is_active = true WHERE id = ?', [$this->originallyActiveRulesetId]);
        }

        // These DELETEs/UPDATEs bypass the ORM, so the L2C NONSTRICT_READ_WRITE cache for
        // Card::$rulesets (populated by tests that read it) isn't auto-invalidated by them and
        // would otherwise leak stale, now-deleted Rulesets into other tests.
        $entityManager->getCache()?->evictCollectionRegion(Card::class, 'rulesets');

        parent::tearDown();
    }

    public function testCreateRulesetPersistsANewCompleteRulesetClonedFromTheActiveOne(): void
    {
        $service = $this->createService();

        /** @var RulesetRepository $rulesetRepository */
        $rulesetRepository = static::getContainer()->get(RulesetRepository::class);
        $activeRuleset = $rulesetRepository->findOneBy([
            'isActive' => true,
        ]);
        $this->assertNotNull($activeRuleset);
        $expectedCardCount = $activeRuleset->getCards()->count();

        $ruleset = $service->createRuleset('Errata Integration Test');

        $this->assertNotNull($ruleset->getId());
        $this->assertFalse($ruleset->isActive());
        $this->assertCount($expectedCardCount, $ruleset->getCards());
        $this->assertTrue($service->isComplete($ruleset));
    }

    public function testSwapCardPersistsTheReplacementInTheDatabase(): void
    {
        $service = $this->createService();
        $container = static::getContainer();

        /** @var CardRepository $cardRepository */
        $cardRepository = $container->get(CardRepository::class);
        $oldCard = $cardRepository->getCard('01001.0');
        $this->assertNotNull($oldCard);

        $ruleset = $service->createRuleset('Errata Swap Test');

        $newCard = new CompanionCard()->setId('01001.errata-test')
            ->setTitle($oldCard->getTitle())
            ->setPublishedSet($oldCard->getPublishedSet())
            ->setPosition($oldCard->getPosition())
            ->setRarity($oldCard->getRarity())
            ->setUnique($oldCard->isUnique());

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->persist($newCard);
        $entityManager->flush();

        $service->swapCard($ruleset, $oldCard, $newCard);

        $connection = $entityManager->getConnection();
        $this->assertFalse((bool) $connection->fetchOne(
            'SELECT 1 FROM card_ruleset WHERE card_id = ? AND ruleset_id = ?',
            [$oldCard->getId(), $ruleset->getId()],
        ));
        $this->assertTrue((bool) $connection->fetchOne(
            'SELECT 1 FROM card_ruleset WHERE card_id = ? AND ruleset_id = ?',
            [$newCard->getId(), $ruleset->getId()],
        ));
        $this->assertTrue($service->isComplete($ruleset));
    }

    public function testSwapCardInvalidatesTheCachedRulesetsCollectionForBothSides(): void
    {
        $service = $this->createService();
        $container = static::getContainer();

        /** @var CardRepository $cardRepository */
        $cardRepository = $container->get(CardRepository::class);
        /** @var RulesetRepository $rulesetRepository */
        $rulesetRepository = $container->get(RulesetRepository::class);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        $ruleset = $service->createRuleset('Errata Cache Test');

        $oldCard = $cardRepository->getCard('01001.0');
        $this->assertNotNull($oldCard);

        // Prime the L2C collection cache (Card::$rulesets / Ruleset::$cards are NONSTRICT_READ_WRITE)
        // on both sides before mutating them.
        $this->assertTrue($oldCard->getRulesets()->contains($ruleset));
        $this->assertTrue($ruleset->getCards()->contains($oldCard));

        $newCard = new CompanionCard()->setId('01001.errata-test')
            ->setTitle($oldCard->getTitle())
            ->setPublishedSet($oldCard->getPublishedSet())
            ->setPosition($oldCard->getPosition())
            ->setRarity($oldCard->getRarity())
            ->setUnique($oldCard->isUnique());
        $entityManager->persist($newCard);
        $entityManager->flush();

        $service->swapCard($ruleset, $oldCard, $newCard);

        // Detach everything: a re-fetch must now go through L2C rather than the identity map, so
        // this proves the swap actually invalidated the cached collections, not just the DB rows.
        $entityManager->clear();

        $reloadedRuleset = $rulesetRepository->find($ruleset->getId());
        $this->assertNotNull($reloadedRuleset);
        $reloadedOldCard = $cardRepository->find('01001.0');
        $this->assertNotNull($reloadedOldCard);

        $this->assertFalse($reloadedRuleset->getCards()->exists(static fn (int $key, Card $card): bool => $card->getId() === '01001.0'));
        $this->assertTrue($reloadedRuleset->getCards()->exists(static fn (int $key, Card $card): bool => $card->getId() === '01001.errata-test'));
        $this->assertFalse($reloadedOldCard->getRulesets()->exists(static fn (int $key, Ruleset $r): bool => $r->getId() === $ruleset->getId()));
    }

    public function testActivatePersistsTheSwapOfIsActiveInATransaction(): void
    {
        $service = $this->createService();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();

        $ruleset = $service->createRuleset('Errata Activate Test');

        $service->activate($ruleset);

        $activeNames = $connection->fetchFirstColumn('SELECT name FROM ruleset WHERE is_active = true');
        $this->assertSame(['Errata Activate Test'], $activeNames);
    }

    private function createService(): RulesetService
    {
        $container = static::getContainer();
        /** @var PublishedSetRepository $publishedSetRepository */
        $publishedSetRepository = $container->get(PublishedSetRepository::class);
        /** @var CardRepository $cardRepository */
        $cardRepository = $container->get(CardRepository::class);
        /** @var RulesetRepository $rulesetRepository */
        $rulesetRepository = $container->get(RulesetRepository::class);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        return new RulesetService($publishedSetRepository, $cardRepository, $rulesetRepository, $entityManager);
    }
}
