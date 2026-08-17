<?php

declare(strict_types=1);

namespace App\Flash;

use Psr\Log\LoggerInterface;

class FlashLoggerBridge implements LoggerInterface
{
    private const array LEVELS = [
        'emergency' => FlashType::Danger,
        'alert' => FlashType::Danger,
        'critical' => FlashType::Danger,
        'error' => FlashType::Danger,
        'warning' => FlashType::Warning,
        'notice' => FlashType::Info,
        'info' => FlashType::Info,
        'debug' => FlashType::Secondary,
    ];

    public function __construct(
        private readonly FlashService $flashService,
    ) {
    }

    #[\Override]
    public function emergency(\Stringable|string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    #[\Override]
    public function alert(\Stringable|string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    #[\Override]
    public function critical(\Stringable|string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    #[\Override]
    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    #[\Override]
    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    #[\Override]
    public function notice(\Stringable|string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    #[\Override]
    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    #[\Override]
    public function debug(\Stringable|string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * @param string $level
     */
    #[\Override]
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->flashService->addFlash(
            self::LEVELS[$level] ?? FlashType::Primary,
            $message instanceof \Stringable ? $message->__toString() : $message
        );
    }
}
