<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\SearchController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(SearchController::class)]
class SearchControllerTest extends WebTestCase
{
    /**
     * @return array<array{string, int}>
     */
    public static function searchProvider(): array
    {
        return [
            ['t:unit', 60],
            ['scrap', 3],
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
}
