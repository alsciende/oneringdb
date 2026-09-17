<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\AbstractCardCrudController;
use App\Controller\Admin\CompanionCardCrudController;
use App\Entity\Card;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Companion is the richest Type in fields (culture, twilightCost, strength, vitality, signet,
 * subtype), so it's used here to exercise AbstractCardCrudController's shared logic (persistEntity()
 * id computation, forced revision 0, the required-culture and cross-Type unique-position
 * validation, detachOtherRevisionsFromRulesets(), and the redirect-after-save override) in depth.
 */
#[CoversClass(CompanionCardCrudController::class)]
#[CoversClass(AbstractCardCrudController::class)]
class CompanionCardCrudControllerTest extends WebTestCase
{
    /**
     * These tests persist real rows / mutate real card_ruleset rows outside of any rolled-back
     * transaction (this project has no transactional test isolation), so clean up everything they
     * change to avoid leaking state into other tests.
     */
    protected function tearDown(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();
        $connection->executeStatement("DELETE FROM card WHERE published_set_id = '01' AND position = 999");
        // Restore 01012.0/01012.1's original Ruleset links in case
        // testSavingACardDetachesTheSavedRulesetsFromOtherRevisions() changed them.
        $connection->executeStatement("DELETE FROM card_ruleset WHERE card_id = '01012.0' AND ruleset_id = 2");
        $connection->executeStatement("INSERT INTO card_ruleset (card_id, ruleset_id) SELECT '01012.1', 2 WHERE NOT EXISTS (SELECT 1 FROM card_ruleset WHERE card_id = '01012.1' AND ruleset_id = 2)");
        // Bypasses the ORM, so neither the Card entity nor its L2C NONSTRICT_READ_WRITE cache
        // entries (entity itself + the rulesets collection) are auto-invalidated by it.
        $entityManager->getCache()?->evictEntityRegion(Card::class);
        $entityManager->getCache()?->evictCollectionRegion(Card::class, 'rulesets');

        parent::tearDown();
    }

    public function testNewPageShowsTheTitleAndTheExpectedFields(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/admin/companion-card/new');

        self::assertResponseIsSuccessful();
        self::assertSame('Create Companion Card', $crawler->filter('h1.title')->text());

        foreach (['culture', 'twilightCost', 'strength', 'vitality', 'signet', 'subtype', 'position'] as $field) {
            self::assertGreaterThanOrEqual(
                1,
                $crawler->filter(sprintf('[name="CompanionCard[%s]"]', $field))->count(),
                sprintf('Missing "%s" field on the New page', $field),
            );
        }
    }

    public function testSubmittingAValidNewFormPersistsACardWithAComputedIdAndRevisionZero(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/admin/companion-card/new');

        $form = $crawler->selectButton('saveAndReturn')->form();
        $form['CompanionCard[title]'] = 'Test Card';
        $form['CompanionCard[position]'] = '999';
        $form['CompanionCard[publishedSet]'] = '01';
        $form['CompanionCard[rarity]'] = 'common';
        $form['CompanionCard[culture]'] = 'dwarven';
        $client->submit($form);

        // "Save and return" redirects to the generic CardCrudController's Index, not this
        // (per-Type) controller's own Index.
        self::assertResponseRedirects('http://localhost/admin/card');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $card = $entityManager->find(Card::class, '01999.0');

        self::assertNotNull($card);
        self::assertSame(0, $card->getRevision());
        self::assertSame('Test Card', $card->getTitle());
    }

    public function testSubmittingANewFormWithoutCultureShowsAValidationError(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/admin/companion-card/new');

        $form = $crawler->selectButton('saveAndReturn')->form();
        $form['CompanionCard[title]'] = 'Test Card';
        $form['CompanionCard[position]'] = '999';
        $form['CompanionCard[publishedSet]'] = '01';
        $form['CompanionCard[rarity]'] = 'common';
        $crawler = $client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertGreaterThanOrEqual(1, $crawler->filter('.invalid-feedback')->count());

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        self::assertNull($entityManager->find(Card::class, '01999.0'));
    }

    public function testSubmittingANewFormWithACollidingPositionShowsAValidationError(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/admin/companion-card/new');

        // 01001.0 (a Ring, a different Type) already occupies position 1 of PublishedSet "01" at
        // revision 0 — the uniqueness check must apply across Types, not just within Companion.
        $form = $crawler->selectButton('saveAndReturn')->form();
        $form['CompanionCard[title]'] = 'Test Card';
        $form['CompanionCard[position]'] = '1';
        $form['CompanionCard[publishedSet]'] = '01';
        $form['CompanionCard[rarity]'] = 'common';
        $form['CompanionCard[culture]'] = 'dwarven';
        $crawler = $client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString(
            'A card already exists at this position and revision for this set.',
            $crawler->filter('.invalid-feedback')->text(),
        );
    }

    public function testEditPageShowsTheEditTitle(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/admin/companion-card/01007.0/edit');

        self::assertResponseIsSuccessful();
        self::assertSame('Edit Companion Card', $crawler->filter('h1.title')->text());
    }

    public function testSavingACardDetachesTheSavedRulesetsFromOtherRevisions(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // 01012.0/01012.1 are two revisions of the same Companion (Gimli). Ruleset 2 starts out
        // linked only to 01012.1.
        self::assertRulesetIsLinkedTo($entityManager, rulesetId: 2, cardId: '01012.1');
        self::assertRulesetIsNotLinkedTo($entityManager, rulesetId: 2, cardId: '01012.0');

        $crawler = $client->request(Request::METHOD_GET, '/admin/companion-card/01012.0/edit');

        $form = $crawler->selectButton('saveAndReturn')->form();
        $form['CompanionCard[rulesets]'] = ['1', '2'];
        $client->submit($form);

        self::assertResponseRedirects('http://localhost/admin/card');

        $entityManager->clear();
        self::assertRulesetIsLinkedTo($entityManager, rulesetId: 2, cardId: '01012.0');
        self::assertRulesetIsNotLinkedTo($entityManager, rulesetId: 2, cardId: '01012.1');
    }

    private static function assertRulesetIsLinkedTo(EntityManagerInterface $entityManager, int $rulesetId, string $cardId): void
    {
        self::assertNotFalse(
            $entityManager->getConnection()->fetchOne('SELECT 1 FROM card_ruleset WHERE card_id = ? AND ruleset_id = ?', [$cardId, $rulesetId]),
            sprintf('Expected Ruleset %d to be linked to Card "%s"', $rulesetId, $cardId),
        );
    }

    private static function assertRulesetIsNotLinkedTo(EntityManagerInterface $entityManager, int $rulesetId, string $cardId): void
    {
        self::assertFalse(
            $entityManager->getConnection()->fetchOne('SELECT 1 FROM card_ruleset WHERE card_id = ? AND ruleset_id = ?', [$cardId, $rulesetId]),
            sprintf('Expected Ruleset %d to not be linked to Card "%s"', $rulesetId, $cardId),
        );
    }
}
