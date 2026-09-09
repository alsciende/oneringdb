<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\Translation\DataCollector\TranslationDataCollector;

trait TranslationCollector
{
    public static function assertNoMissingTranslations(): void
    {
        $client = static::getClient();
        self::assertInstanceOf(KernelBrowser::class, $client, 'Le client web n’est pas disponible, vérifie que APP_ENV=test.');

        $profile = $client->getProfile();
        self::assertInstanceOf(Profile::class, $profile, 'Le profiler n’est pas disponible, vérifie que APP_ENV=test et profiler activé.');

        $collector = $profile->getCollector('translation');
        self::assertInstanceOf(TranslationDataCollector::class, $collector);

        self::assertEquals(0, $collector->getCountMissings());
    }
}
