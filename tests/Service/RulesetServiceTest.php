<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Card;
use App\Entity\CardTypes\CompanionCard;
use App\Entity\PublishedSet;
use App\Entity\Ruleset;
use App\Repository\CardRepository;
use App\Repository\PublishedSetRepository;
use App\Repository\RulesetRepository;
use App\Service\RulesetService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Cache;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RulesetService::class)]
class RulesetServiceTest extends TestCase
{
    public function testFindMissingPositionsReturnsEmptyArrayWhenEveryPositionIsCovered(): void
    {
        $set1 = new PublishedSet()->setId('01')->setName('Set 1')->setPosition(1);
        $set2 = new PublishedSet()->setId('02')->setName('Set 2')->setPosition(2);
        $ruleset = new Ruleset()->setName('Decipher Standard Rules')->setActive(true);

        $publishedSetRepository = $this->createMock(PublishedSetRepository::class);
        $publishedSetRepository->method('findAll')->willReturn([$set1, $set2]);

        $cardRepository = $this->createMock(CardRepository::class);
        $cardRepository->method('findDistinctPositions')->willReturnMap([
            [$set1, null, [1, 2, 3]],
            [$set1, $ruleset, [1, 2, 3]],
            [$set2, null, [1, 2]],
            [$set2, $ruleset, [1, 2]],
        ]);

        $service = $this->createService(publishedSetRepository: $publishedSetRepository, cardRepository: $cardRepository);

        $this->assertSame([], $service->findMissingPositions($ruleset));
        $this->assertTrue($service->isComplete($ruleset));
    }

    public function testFindMissingPositionsReturnsTheMissingPositionsPerPublishedSet(): void
    {
        $set1 = new PublishedSet()->setId('01')->setName('Set 1')->setPosition(1);
        $set2 = new PublishedSet()->setId('02')->setName('Set 2')->setPosition(2);
        $ruleset = new Ruleset()->setName('Errata 1')->setActive(true);

        $publishedSetRepository = $this->createMock(PublishedSetRepository::class);
        $publishedSetRepository->method('findAll')->willReturn([$set1, $set2]);

        $cardRepository = $this->createMock(CardRepository::class);
        $cardRepository->method('findDistinctPositions')->willReturnMap([
            [$set1, null, [1, 2, 3]],
            [$set1, $ruleset, [1, 3]],
            [$set2, null, [1, 2]],
            [$set2, $ruleset, [1, 2]],
        ]);

        $service = $this->createService(publishedSetRepository: $publishedSetRepository, cardRepository: $cardRepository);

        $this->assertSame([
            '01' => [2],
        ], $service->findMissingPositions($ruleset));
        $this->assertFalse($service->isComplete($ruleset));
    }

    public function testCreateRulesetCopiesCardRulesetRowsFromTheActiveRulesetWhenNoneIsGiven(): void
    {
        $activeRuleset = new Ruleset()->setName('Decipher Standard Rules')->setActive(true);
        $this->setRulesetId($activeRuleset, 7);

        $rulesetRepository = $this->createMock(RulesetRepository::class);
        $rulesetRepository->method('findOneBy')->with([
            'isActive' => true,
        ])->willReturn($activeRuleset);

        $entityManager = $this->createServiceEntityManagerMock(baseRulesetId: 7, newRulesetId: 99);

        $service = $this->createService(rulesetRepository: $rulesetRepository, entityManager: $entityManager);

        $ruleset = $service->createRuleset('Errata 1');

        $this->assertSame('Errata 1', $ruleset->getName());
        $this->assertFalse($ruleset->isActive());
        $this->assertSame(99, $ruleset->getId());
    }

    public function testCreateRulesetCopiesCardRulesetRowsFromTheGivenBaseRuleset(): void
    {
        $baseRuleset = new Ruleset()->setName('Errata 1');
        $this->setRulesetId($baseRuleset, 3);

        $rulesetRepository = $this->createMock(RulesetRepository::class);
        $rulesetRepository->expects($this->never())->method('findOneBy');

        $entityManager = $this->createServiceEntityManagerMock(baseRulesetId: 3, newRulesetId: 100);

        $service = $this->createService(rulesetRepository: $rulesetRepository, entityManager: $entityManager);

        $ruleset = $service->createRuleset('Errata 2', $baseRuleset);

        $this->assertSame(100, $ruleset->getId());
    }

    public function testCreateRulesetThrowsWhenNoActiveRulesetExistsAndNoneIsGiven(): void
    {
        $rulesetRepository = $this->createMock(RulesetRepository::class);
        $rulesetRepository->method('findOneBy')->willReturn(null);

        $service = $this->createService(rulesetRepository: $rulesetRepository);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot find an active Ruleset');
        $service->createRuleset('Errata 1');
    }

    public function testSwapCardReplacesTheOldCardWithTheNewCardInATransaction(): void
    {
        $ruleset = new Ruleset()->setName('Errata 1');
        $oldCard = new CompanionCard()->setId('01001.0')->setTitle('Aragorn');
        $newCard = new CompanionCard()->setId('01001.1')->setTitle('Aragorn');
        $ruleset->addCard($oldCard);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('wrapInTransaction')
            ->willReturnCallback(static fn (callable $func) => $func());

        $service = $this->createService(entityManager: $entityManager);
        $service->swapCard($ruleset, $oldCard, $newCard);

        $this->assertFalse($ruleset->getCards()->contains($oldCard));
        $this->assertTrue($ruleset->getCards()->contains($newCard));
        $this->assertFalse($oldCard->getRulesets()->contains($ruleset));
        $this->assertTrue($newCard->getRulesets()->contains($ruleset));
    }

    public function testSwapCardThrowsWhenTheOldCardIsNotLinkedToTheRuleset(): void
    {
        $ruleset = new Ruleset()->setName('Errata 1');
        $oldCard = new CompanionCard()->setId('01001.0')->setTitle('Aragorn');
        $newCard = new CompanionCard()->setId('01001.1')->setTitle('Aragorn');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('wrapInTransaction');

        $service = $this->createService(entityManager: $entityManager);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Card "01001.0" is not linked to Ruleset "Errata 1"');
        $service->swapCard($ruleset, $oldCard, $newCard);
    }

    public function testActivateThrowsWhenTheRulesetIsNotComplete(): void
    {
        $set1 = new PublishedSet()->setId('01')->setName('Set 1')->setPosition(1);
        $ruleset = new Ruleset()->setName('Errata 1');

        $publishedSetRepository = $this->createMock(PublishedSetRepository::class);
        $publishedSetRepository->method('findAll')->willReturn([$set1]);

        $cardRepository = $this->createMock(CardRepository::class);
        $cardRepository->method('findDistinctPositions')->willReturnMap([
            [$set1, null, [1, 2]],
            [$set1, $ruleset, [1]],
        ]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('wrapInTransaction');

        $service = $this->createService(publishedSetRepository: $publishedSetRepository, cardRepository: $cardRepository, entityManager: $entityManager);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Ruleset "Errata 1" is not complete and cannot be activated');
        $service->activate($ruleset);
    }

    public function testActivateDeactivatesThePreviouslyActiveRulesetBeforeActivatingTheNewOne(): void
    {
        $publishedSetRepository = $this->createMock(PublishedSetRepository::class);
        $publishedSetRepository->method('findAll')->willReturn([]);

        $previouslyActive = new Ruleset()->setName('Decipher Standard Rules')->setActive(true);
        $ruleset = new Ruleset()->setName('Errata 1');

        $rulesetRepository = $this->createMock(RulesetRepository::class);
        $rulesetRepository->method('findOneBy')->with([
            'isActive' => true,
        ])->willReturn($previouslyActive);

        $flushSnapshots = [];

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('wrapInTransaction')
            ->willReturnCallback(static fn (callable $func) => $func());
        $entityManager->method('flush')->willReturnCallback(function () use (&$flushSnapshots, $previouslyActive, $ruleset): void {
            $flushSnapshots[] = [$previouslyActive->isActive(), $ruleset->isActive()];
        });

        $service = $this->createService(publishedSetRepository: $publishedSetRepository, rulesetRepository: $rulesetRepository, entityManager: $entityManager);

        $service->activate($ruleset);

        // The old Ruleset must be flushed as deactivated *before* the new one is flushed as
        // active, otherwise the unique constraint on `ruleset.is_active` would be violated.
        $this->assertSame([
            [false, false],
            [false, true],
        ], $flushSnapshots);
        $this->assertFalse($previouslyActive->isActive());
        $this->assertTrue($ruleset->isActive());
    }

    public function testActivateWhenNoRulesetIsCurrentlyActive(): void
    {
        $publishedSetRepository = $this->createMock(PublishedSetRepository::class);
        $publishedSetRepository->method('findAll')->willReturn([]);

        $ruleset = new Ruleset()->setName('Errata 1');

        $rulesetRepository = $this->createMock(RulesetRepository::class);
        $rulesetRepository->method('findOneBy')->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('wrapInTransaction')
            ->willReturnCallback(static fn (callable $func) => $func());
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService(publishedSetRepository: $publishedSetRepository, rulesetRepository: $rulesetRepository, entityManager: $entityManager);

        $service->activate($ruleset);

        $this->assertTrue($ruleset->isActive());
    }

    public function testActivateIsANoOpOnTheAlreadyActiveRuleset(): void
    {
        $publishedSetRepository = $this->createMock(PublishedSetRepository::class);
        $publishedSetRepository->method('findAll')->willReturn([]);

        $ruleset = new Ruleset()->setName('Decipher Standard Rules')->setActive(true);

        $rulesetRepository = $this->createMock(RulesetRepository::class);
        $rulesetRepository->method('findOneBy')->willReturn($ruleset);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('wrapInTransaction')
            ->willReturnCallback(static fn (callable $func) => $func());
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService(publishedSetRepository: $publishedSetRepository, rulesetRepository: $rulesetRepository, entityManager: $entityManager);

        $service->activate($ruleset);

        $this->assertTrue($ruleset->isActive());
    }

    public function testFindRulesetHistoryReturnsOnlyTheRulesetsWhereTheCardChangedSortedByDate(): void
    {
        $oldCard = new CompanionCard()->setId('01001.0')->setTitle('Aragorn');
        $newCard = new CompanionCard()->setId('01001.1')->setTitle('Aragorn');

        $rulesetC = new Ruleset()->setName('Current Rulings Document April 5, 2004')->setPublicationDate(new \DateTime('2004-04-05'));
        $rulesetB = new Ruleset()->setName('Comprehensive Rules 1.0')->setPublicationDate(new \DateTime('2003-06-01'));
        $rulesetA = new Ruleset()->setName('Base Printing')->setPublicationDate(new \DateTime('2003-01-01'));

        // Deliberately linked out of chronological order, to prove the sort doesn't rely on it.
        $oldCard->addRuleset($rulesetB);
        $oldCard->addRuleset($rulesetA);
        $newCard->addRuleset($rulesetC);

        $cardRepository = $this->createMock(CardRepository::class);
        $cardRepository->method('findRevisions')->with($oldCard)->willReturn([$oldCard, $newCard]);

        $service = $this->createService(cardRepository: $cardRepository);

        // rulesetB is dropped: it still points to $oldCard, just like rulesetA right before it.
        $this->assertSame([
            [
                'card' => $oldCard,
                'ruleset' => $rulesetA,
            ],
            [
                'card' => $newCard,
                'ruleset' => $rulesetC,
            ],
        ], $service->findRulesetHistory($oldCard));
    }

    private function setRulesetId(Ruleset $ruleset, int $id): void
    {
        new \ReflectionProperty(Ruleset::class, 'id')->setValue($ruleset, $id);
    }

    /**
     * Mocks an EntityManager whose flush() assigns $newRulesetId to the just-persisted Ruleset
     * (simulating GeneratedValue id assignment), and whose Connection expects exactly the bulk
     * card_ruleset copy statement createRuleset() should issue.
     */
    private function createServiceEntityManagerMock(int $baseRulesetId, int $newRulesetId): EntityManagerInterface
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('executeStatement')
            ->with(
                'INSERT INTO card_ruleset (card_id, ruleset_id) SELECT card_id, ? FROM card_ruleset WHERE ruleset_id = ?',
                [$newRulesetId, $baseRulesetId],
            );

        $cache = $this->createMock(Cache::class);
        $cache->expects($this->once())->method('evictCollectionRegion')->with(Card::class, 'rulesets');

        $createdRuleset = null;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $entityManager->method('getCache')->willReturn($cache);
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Ruleset::class))
            ->willReturnCallback(function (object $entity) use (&$createdRuleset): void {
                $createdRuleset = $entity;
            });
        $entityManager->method('flush')->willReturnCallback(function () use (&$createdRuleset, $newRulesetId): void {
            if ($createdRuleset instanceof Ruleset) {
                $this->setRulesetId($createdRuleset, $newRulesetId);
            }
        });
        $entityManager->expects($this->once())->method('refresh')->with($this->isInstanceOf(Ruleset::class));

        return $entityManager;
    }

    private function createService(
        ?PublishedSetRepository $publishedSetRepository = null,
        ?CardRepository $cardRepository = null,
        ?RulesetRepository $rulesetRepository = null,
        ?EntityManagerInterface $entityManager = null,
    ): RulesetService {
        return new RulesetService(
            $publishedSetRepository ?? $this->createMock(PublishedSetRepository::class),
            $cardRepository ?? $this->createMock(CardRepository::class),
            $rulesetRepository ?? $this->createMock(RulesetRepository::class),
            $entityManager ?? $this->createMock(EntityManagerInterface::class),
        );
    }
}
