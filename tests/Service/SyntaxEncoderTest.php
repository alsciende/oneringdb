<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\PublishedSet;
use App\Enum\Culture;
use App\Enum\Subtype;
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
            [
                new AdvancedCardSearch()->setTitle('rosie')->setTwilightCost(4),
                'rosie o:4',
            ],
            [
                new AdvancedCardSearch()->setTitle('rosie')->setSubtype(Subtype::Hobbit),
                'rosie s:Hobbit',
            ],
            [
                new AdvancedCardSearch()->setTitle('rosie')->setText('spot a hobbit'),
                'rosie x:"spot a hobbit"',
            ],
            [
                new AdvancedCardSearch()->setTitle('rosie')->setPublishedSet(new PublishedSet()->setId('01')),
                'rosie p:01',
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
