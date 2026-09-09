<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\PublishedSetController;
use App\Tests\DoctrineCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(PublishedSetController::class)]
class PublishedSetControllerTest extends WebTestCase
{
    use DoctrineCollector;

    public function testSetPageAlwaysHitsTheDatabaseTwice(): void
    {
        $client = static::createClient();
        self::resetQueryCount();

        $client->enableProfiler();
        $client->request(Request::METHOD_GET, '/set/01');
        self::assertResponseIsSuccessful();

        // Les résultats de recherche sont marqués non-cacheable : 1 COUNT + 1 SELECT à chaque appel.
        self::assertDoctrineQueryCount(2);
    }
}
