<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Enum\Subtype;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(Subtype::class)]
class SubtypeTest extends TestCase
{
    public function testTransDelegatesToTheTranslator(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->once())
            ->method('trans')
            ->with('Hobbit', [], 'subtypes', null)
            ->willReturn('Hobbit');

        $this->assertSame('Hobbit', Subtype::Hobbit->trans($translator));
    }
}
