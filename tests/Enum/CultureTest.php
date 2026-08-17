<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Enum\Culture;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Culture::class)]
class CultureTest extends TestCase
{
    public function testSize(): void
    {
        $cases = Culture::cases();
        $this->assertCount(13, $cases);
    }
}
