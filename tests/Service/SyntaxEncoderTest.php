<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Enum\Culture;
use App\Enum\Type;
use App\Search\AdvancedCardSearch;
use App\Service\SyntaxEncoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SyntaxEncoder::class)]
class SyntaxEncoderTest extends TestCase
{
    /**
     * @return array<array{AdvancedCardSearch, string}>
     */
    public static function encodeProvider(): array
    {
        return [
            [
                new AdvancedCardSearch()->setTitle('rosie'),
                'rosie',
            ],
            [
                new AdvancedCardSearch()->setTitle('rosie')->setType(Type::Ally),
                'rosie t:ally',
            ],
            [
                new AdvancedCardSearch()->setTitle('rosie')->setCulture(Culture::Shire),
                'rosie c:sh',
            ],
            [
                new AdvancedCardSearch()->setTitle('rosie cotton'),
                '"rosie cotton"',
            ],
        ];
    }

    #[DataProvider('encodeProvider')]
    public function testEncode(AdvancedCardSearch $search, string $query): void
    {
        $encoder = new SyntaxEncoder();
        $this->assertEquals($query, $encoder->encode($search));
    }
}
