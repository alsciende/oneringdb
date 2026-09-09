<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\CardController;
use App\Tests\DoctrineCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(CardController::class)]
class CardControllerTest extends WebTestCase
{
    use DoctrineCollector;

    public function testSecondVisitIsServedFromTheDoctrineCache(): void
    {
        $client = static::createClient();
        self::resetQueryCount();

        $client->enableProfiler();
        $client->request(Request::METHOD_GET, '/card/01001');
        self::assertResponseIsSuccessful();

        $client->enableProfiler();
        $client->request(Request::METHOD_GET, '/card/01001');
        self::assertResponseIsSuccessful();

        // Card et PublishedSet sont en cache Doctrine (L2C, READ_ONLY) : le 2e appel ne doit exécuter aucune requête SQL.
        self::assertDoctrineQueryCount(0);
    }

    public function testCardInTheMiddleOfASetLinksToPreviousAndNextCard(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/card/01004');

        self::assertResponseIsSuccessful();
        self::assertSame(1, $crawler->filter('a[href="/card/01003"]')->count());
        self::assertSame(1, $crawler->filter('a[href="/card/01005"]')->count());
    }

    public function testFirstCardOfASetHasNoLinkToAPreviousCard(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/card/01001');

        self::assertResponseIsSuccessful();
        self::assertSame(0, $crawler->filter('a[href="/card/01000"]')->count());
        self::assertSame(1, $crawler->filter('a[href="/card/01002"]')->count());
    }

    public function testLastCardOfASetHasNoLinkToANextCard(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/card/01365');

        self::assertResponseIsSuccessful();
        self::assertSame(1, $crawler->filter('a[href="/card/01364"]')->count());
        self::assertSame(0, $crawler->filter('a[href="/card/01366"]')->count());
    }
}
