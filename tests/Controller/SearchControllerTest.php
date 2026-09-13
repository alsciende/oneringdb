<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\SearchController;
use App\Tests\TranslationCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(SearchController::class)]
class SearchControllerTest extends WebTestCase
{
    use TranslationCollector;

    /**
     * @return array<array{string, int}>
     */
    public static function searchProvider(): array
    {
        return [
            ['t:ring', 6],
            ['s:elf', 55],
            ['Aragorn', 11],
            ['xxxx', 0],
        ];
    }

    #[DataProvider('searchProvider')]
    public function testSearch(string $query, int $expectedResults): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/');

        $crawler = $client->submitForm('Search', [
            'q' => $query,
        ], 'GET');

        $this->assertResponseIsSuccessful();
        $this->assertEquals($expectedResults, $crawler->filter('table.card-list tbody tr')->count());
    }

    public function testNoMissingTranslationsOnSearchResultsPage(): void
    {
        $client = static::createClient();

        $client->enableProfiler();
        $client->request(Request::METHOD_GET, '/');
        self::assertResponseIsSuccessful();

        $client->enableProfiler();
        $client->submitForm('Search', [
            'q' => 't:ring',
        ], 'GET');
        self::assertResponseIsSuccessful();

        self::assertNoMissingTranslations();
    }
}
