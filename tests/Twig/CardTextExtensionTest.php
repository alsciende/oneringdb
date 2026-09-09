<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Twig\CardTextExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CardTextExtension::class)]
class CardTextExtensionTest extends TestCase
{
    public function testNullTextReturnsEmptyString(): void
    {
        $this->assertSame('', new CardTextExtension()->markup(null));
    }

    public function testKeywordTagIsTransformedIntoASpan(): void
    {
        $result = new CardTextExtension()->markup('<keyword>Ranger</keyword> only.');

        $this->assertSame('<span class="keyword">Ranger</span> only.', $result);
    }

    public function testPhaseTagIsTransformedIntoASpan(): void
    {
        $result = new CardTextExtension()->markup('During the <phase>fellowship phase</phase>.');

        $this->assertSame('During the <span class="phase">fellowship phase</span>.', $result);
    }

    public function testNewlinesAreConvertedToLineBreaks(): void
    {
        $result = new CardTextExtension()->markup("Line 1\nLine 2");

        $this->assertSame("Line 1<br />\nLine 2", $result);
    }
}
