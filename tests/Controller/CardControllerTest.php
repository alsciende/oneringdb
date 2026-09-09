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
}
