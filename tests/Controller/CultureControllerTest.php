<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\CultureController;
use App\Enum\Culture;
use App\Tests\DoctrineCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[CoversClass(CultureController::class)]
class CultureControllerTest extends WebTestCase
{
    use DoctrineCollector;

    /**
     * @return array<array<string>>
     */
    public static function cultureProvider(): array
    {
        return array_map(fn (Culture $culture): array => [$culture->value], Culture::cases());
    }

    #[DataProvider('cultureProvider')]
    public function testCulturePage(string $cultureId): void
    {
        $client = static::createClient();
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/culture/' . $cultureId);

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('table.card-list tbody tr')->count(), 'Empty card list!');
    }

    public function testCulturePageAlwaysHitsTheDatabaseTwice(): void
    {
        $client = static::createClient();
        self::resetQueryCount();

        $client->enableProfiler();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/culture/shire');
        $this->assertResponseIsSuccessful();

        // Les résultats de recherche sont marqués non-cacheable : 1 COUNT + 1 SELECT à chaque appel.
        self::assertDoctrineQueryCount(2);
    }
}
