<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\HomeController;
use App\Tests\DoctrineCollector;
use App\Tests\TranslationCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(HomeController::class)]
class HomeControllerTest extends WebTestCase
{
    use DoctrineCollector;
    use TranslationCollector;

    public function testSecondVisitIsServedFromTheDoctrineCache(): void
    {
        $client = static::createClient();

        $client->enableProfiler();
        $client->request(Request::METHOD_GET, '/');
        self::assertResponseIsSuccessful();

        $client->enableProfiler();
        $client->request(Request::METHOD_GET, '/');
        self::assertResponseIsSuccessful();

        self::assertDoctrineQueryCount(0);
    }

    public function testNoMissingTranslationsOnHomePage(): void
    {
        $client = static::createClient();

        $client->enableProfiler();
        $client->request(Request::METHOD_GET, '/');
        self::assertResponseIsSuccessful();

        self::assertNoMissingTranslations();
    }
}
