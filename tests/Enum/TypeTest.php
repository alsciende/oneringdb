<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Enum\Type;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(Type::class)]
class TypeTest extends TestCase
{
    public function testTransDelegatesToTheTranslator(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->once())
            ->method('trans')
            ->with('ally', [], 'types', 'fr')
            ->willReturn('Allié');

        $this->assertSame('Allié', Type::Ally->trans($translator, 'fr'));
    }
}
