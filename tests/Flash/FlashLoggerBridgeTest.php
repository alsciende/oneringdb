<?php

declare(strict_types=1);

namespace App\Tests\Flash;

use App\Flash\FlashLoggerBridge;
use App\Flash\FlashService;
use App\Flash\FlashType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(FlashLoggerBridge::class)]
class FlashLoggerBridgeTest extends TestCase
{
    /**
     * @return array<array{string, FlashType}>
     */
    public static function levelProvider(): array
    {
        return [
            ['emergency', FlashType::Danger],
            ['alert', FlashType::Danger],
            ['critical', FlashType::Danger],
            ['error', FlashType::Danger],
            ['warning', FlashType::Warning],
            ['notice', FlashType::Info],
            ['info', FlashType::Info],
            ['debug', FlashType::Secondary],
        ];
    }

    #[DataProvider('levelProvider')]
    public function testEachPsrLevelMapsToTheExpectedFlashType(string $level, FlashType $expected): void
    {
        $flashService = $this->createMock(FlashService::class);
        $flashService->expects($this->once())
            ->method('addFlash')
            ->with($expected, 'a message');

        $bridge = new FlashLoggerBridge($flashService);
        $bridge->{$level}('a message');
    }

    public function testUnknownLevelFallsBackToPrimary(): void
    {
        $flashService = $this->createMock(FlashService::class);
        $flashService->expects($this->once())
            ->method('addFlash')
            ->with(FlashType::Primary, 'a message');

        $bridge = new FlashLoggerBridge($flashService);
        $bridge->log('unknown-level', 'a message');
    }

    public function testAStringableMessageIsConvertedToAString(): void
    {
        $flashService = $this->createMock(FlashService::class);
        $flashService->expects($this->once())
            ->method('addFlash')
            ->with(FlashType::Info, 'stringable message');

        $message = new class implements \Stringable {
            public function __toString(): string
            {
                return 'stringable message';
            }
        };

        $bridge = new FlashLoggerBridge($flashService);
        $bridge->info($message);
    }
}
