<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\CardController;
use App\Tests\DoctrineCollector;
use App\Tests\TranslationCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(CardController::class)]
class CardControllerTest extends WebTestCase
{
    use DoctrineCollector;
    use TranslationCollector;

    public function testSecondVisitIsServedFromTheDoctrineCache(): void
    {
        $client = static::createClient();
        self::resetQueryCount();

        $client->enableProfiler();
        $client->request(Request::METHOD_GET, '/card/01001.0');
        self::assertResponseIsSuccessful();

        $client->enableProfiler();
        $client->request(Request::METHOD_GET, '/card/01001.0');
        self::assertResponseIsSuccessful();

        // Card et PublishedSet sont en cache Doctrine (L2C, READ_ONLY) ; la collection Card::$rulesets
        // (utilisée par le tableau de révisions) est en cache L2C NONSTRICT_READ_WRITE (voir CLAUDE.md) :
        // le 2e appel ne doit exécuter aucune requête SQL.
        self::assertDoctrineQueryCount(0);
    }

    public function testSecondVisitOfACardWithMultipleRevisionsIsServedFromTheDoctrineCache(): void
    {
        $client = static::createClient();
        self::resetQueryCount();

        // 01003 has 2 revisions: without collection-level L2C caching, building the revision
        // history table would issue one Card::$rulesets lazy-load query per revision (N+1).
        $client->enableProfiler();
        $client->request(Request::METHOD_GET, '/card/01003.0');
        self::assertResponseIsSuccessful();

        $client->enableProfiler();
        $client->request(Request::METHOD_GET, '/card/01003.0');
        self::assertResponseIsSuccessful();

        self::assertDoctrineQueryCount(0);
    }

    public function testCardInTheMiddleOfASetLinksToPreviousAndNextCard(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/card/01004.0');

        self::assertResponseIsSuccessful();
        // 01003 has since been errata'd to revision 1, which is the one linked to the active Ruleset.
        self::assertSame(1, $crawler->filter('a[href="/card/01003.1"]')->count());
        self::assertSame(1, $crawler->filter('a[href="/card/01005.0"]')->count());
    }

    public function testFirstCardOfASetHasNoLinkToAPreviousCard(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/card/01001.0');

        self::assertResponseIsSuccessful();
        self::assertSame(0, $crawler->filter('a[href="/card/01000.0"]')->count());
        self::assertSame(1, $crawler->filter('a[href="/card/01002.0"]')->count());
    }

    public function testLastCardOfASetHasNoLinkToANextCard(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/card/01365.0');

        self::assertResponseIsSuccessful();
        self::assertSame(1, $crawler->filter('a[href="/card/01364.0"]')->count());
        self::assertSame(0, $crawler->filter('a[href="/card/01366.0"]')->count());
    }

    public function testCardPageOpensForARevisionNotInTheActiveRuleset(): void
    {
        $client = static::createClient();
        // 01003 has since been errata'd to revision 1; .0 is no longer linked to the active
        // Ruleset, but its page must still be reachable directly by id.
        $client->request(Request::METHOD_GET, '/card/01003.0');

        self::assertResponseIsSuccessful();
    }

    public function testCardPageShowsTheRevisionHistoryTable(): void
    {
        $client = static::createClient();
        // 01003 has 2 revisions across 7 Rulesets: .0 under the first 2, .1 under the last 5.
        // Only the Ruleset where each revision first appears is shown (2 rows, not 7).
        $crawler = $client->request(Request::METHOD_GET, '/card/01003.0');

        self::assertResponseIsSuccessful();
        self::assertSame(2, $crawler->filter('table.card-list tbody tr')->count());
        // The currently displayed revision (.0) is never linked to itself...
        self::assertSame(0, $crawler->filter('a[href="/card/01003.0"]')->count());
        // ...but the row where the other revision (.1) first appears is.
        self::assertSame(1, $crawler->filter('a[href="/card/01003.1"]')->count());
    }

    public function testNoMissingTranslationsOnCardPage(): void
    {
        $client = static::createClient();

        $client->enableProfiler();
        $client->request(Request::METHOD_GET, '/card/01001.0');
        self::assertResponseIsSuccessful();

        self::assertNoMissingTranslations();
    }

    public function testModalRouteRendersCardDetailWithoutTheSurroundingLayout(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/card/01001.0/modal');

        self::assertResponseIsSuccessful();
        self::assertSame(0, $crawler->filter('nav.navbar')->count());
        self::assertSame(1, $crawler->filter('.card-name-display')->count());
    }
}
