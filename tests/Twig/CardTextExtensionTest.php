<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Twig\CardTextExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

#[CoversClass(CardTextExtension::class)]
class CardTextExtensionTest extends TestCase
{
    public function testGetFiltersRegistersTheCardMarkupFilter(): void
    {
        $filters = new CardTextExtension()->getFilters();

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertSame('card_markup', $filters[0]->getName());
        $this->assertSame(['html'], $filters[0]->getSafe(new \Twig\Node\Node()));
    }

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

    public function testTwilightDigitTagIsTransformedIntoASymbol(): void
    {
        $result = new CardTextExtension()->markup('Twilight cost: <twilight>3</twilight>.');

        $this->assertSame('Twilight cost: ❸.', $result);
    }

    public function testTwilightXTagIsTransformedIntoASymbol(): void
    {
        $result = new CardTextExtension()->markup('Twilight cost: <twilight>X</twilight>.');

        $this->assertSame('Twilight cost: 🅧.', $result);
    }

    public function testUnknownTwilightValueIsLeftUnchanged(): void
    {
        $result = new CardTextExtension()->markup('<twilight>?</twilight>');

        $this->assertSame('<twilight>?</twilight>', $result);
    }
}
