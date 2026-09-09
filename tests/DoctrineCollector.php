<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\Profiler\Profile;

trait DoctrineCollector
{
    public static function getQueries(DoctrineDataCollector $collector): string
    {
        $list = [];
        $index = 0;
        $queries = $collector->getQueries();
        if (isset($queries['default']) && is_array($queries['default'])) {
            foreach ($queries['default'] as $query) {
                if (is_array($query) && isset($query['sql']) && is_string($query['sql'])) {
                    $list[] = sprintf('%d. %s', ++$index, $query['sql']);
                }
            }
        }

        return implode("\n", $list);
    }

    public static function getQueryCount(DoctrineDataCollector $collector, string $connection = 'default'): int
    {
        $queries = $collector->getQueries();

        if (isset($queries[$connection]) && is_array($queries[$connection])) {
            return count($queries[$connection]);
        }

        return 0;
    }

    public static function assertQueryCount(int $expectedQueryCount, DoctrineDataCollector $collector, string $connection = 'default'): void
    {
        self::assertEquals(
            $expectedQueryCount,
            self::getQueryCount($collector),
            "Mauvais nombre de requêtes Doctrine. Requêtes exécutées :\n" . self::getQueries($collector)
        );
    }

    public static function getDoctrineQueryCount(string $connection = 'default'): int
    {
        $client = static::getClient();
        self::assertInstanceOf(KernelBrowser::class, $client, 'Le client web n’est pas disponible, vérifie que APP_ENV=test.');

        $profile = $client->getProfile();
        self::assertInstanceOf(Profile::class, $profile, 'Le profiler n’est pas disponible, vérifie que APP_ENV=test et profiler activé.');

        $collector = $profile->getCollector('db');
        self::assertInstanceOf(DoctrineDataCollector::class, $collector);

        return self::getQueryCount($collector, $connection);
    }

    public static function assertDoctrineQueryCount(int $expectedQueryCount, string $connection = 'default'): void
    {
        $client = static::getClient();
        self::assertInstanceOf(KernelBrowser::class, $client, 'Le client web n’est pas disponible, vérifie que APP_ENV=test.');

        $profile = $client->getProfile();
        self::assertInstanceOf(Profile::class, $profile, 'Le profiler n’est pas disponible, vérifie que APP_ENV=test et profiler activé.');

        $collector = $profile->getCollector('db');
        self::assertInstanceOf(DoctrineDataCollector::class, $collector);

        self::assertQueryCount($expectedQueryCount, $collector, $connection);
    }

    public static function resetQueryCount(): void
    {
        $client = static::getClient();
        self::assertInstanceOf(KernelBrowser::class, $client, 'Le client web n’est pas disponible, vérifie que APP_ENV=test.');

        $client->enableProfiler();
        $crawler = $client->request('GET', '/');
    }
}
