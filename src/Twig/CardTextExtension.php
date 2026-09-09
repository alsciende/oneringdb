<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CardTextExtension extends AbstractExtension
{
    private const array TWILIGHT_SYMBOLS = [
        '1' => '❶',
        '2' => '❷',
        '3' => '❸',
        '4' => '❹',
        '5' => '❺',
        '6' => '❻',
        '7' => '❼',
        '8' => '❽',
        '9' => '❾',
        'X' => '🅧',
    ];

    public function getFilters(): array
    {
        return [
            new TwigFilter('card_markup', $this->markup(...), [
                'is_safe' => ['html'],
            ]),
        ];
    }

    public function markup(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        $text = str_replace(
            ['<keyword>', '</keyword>', '<phase>', '</phase>', '<culture>', '</culture>'],
            ['<span class="keyword">', '</span>', '<span class="phase">', '</span>', '<span class="culture">', '</span>'],
            $text,
        );

        $text = preg_replace_callback(
            '/<twilight>(.)<\/twilight>/',
            static fn (array $matches): string => self::TWILIGHT_SYMBOLS[$matches[1]] ?? $matches[0],
            $text,
        ) ?? $text;

        return nl2br($text);
    }
}
