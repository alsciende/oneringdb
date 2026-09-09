<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CardTextExtension extends AbstractExtension
{
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
            ['<keyword>', '</keyword>', '<phase>', '</phase>'],
            ['<span class="keyword">', '</span>', '<span class="phase">', '</span>'],
            $text,
        );

        return nl2br($text);
    }
}
