<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Card;
use App\Repository\CardRepository;
use App\Service\CardService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(CardService::class)]
class CardServiceIntegrationTest extends KernelTestCase
{
    /**
     * This test persists a real row outside of any rolled-back transaction (this project has no
     * transactional test isolation), so clean it up to avoid leaking state into other tests.
     */
    protected function tearDown(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement("DELETE FROM card WHERE id = '01001.1'");
        // Bypasses the ORM, so the L2C NONSTRICT_READ_WRITE cache isn't auto-invalidated by it.
        $entityManager->getCache()?->evictCollectionRegion(Card::class, 'rulesets');

        parent::tearDown();
    }

    public function testDuplicatePersistsANewRevisionInTheDatabase(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var CardRepository $cardRepository */
        $cardRepository = $container->get(CardRepository::class);
        $card = $cardRepository->getCard('01001.0');
        $this->assertNotNull($card);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $service = new CardService($entityManager);

        $duplicate = $service->duplicate($card);
        $this->assertSame('01001.1', $duplicate->getId());

        $entityManager->clear();

        $reloaded = $cardRepository->find('01001.1');
        $this->assertNotNull($reloaded);
        $this->assertSame(1, $reloaded->getRevision());
        $this->assertSame($card->getTitle(), $reloaded->getTitle());
        $this->assertSame($card->getPublishedSet()->getId(), $reloaded->getPublishedSet()->getId());
        $this->assertCount(0, $reloaded->getRulesets());
    }
}
