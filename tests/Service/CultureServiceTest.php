<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Enum\Culture;
use App\Service\CultureService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(CultureService::class)]
class CultureServiceTest extends TestCase
{
    public function testAllReturnsATranslatedMapIndexedById(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $id, array $parameters = [], ?string $domain = null): string => strtoupper($id)
        );

        $service = new CultureService($translator);
        $result = $service->all();

        $this->assertCount(count(Culture::cases()), $result);
        $this->assertSame('SHIRE', $result[Culture::Shire->value]);
    }
}
