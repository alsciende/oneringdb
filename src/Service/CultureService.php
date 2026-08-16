<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\Culture;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides data about Cultures.
 */
readonly class CultureService
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    /**
     * Return an array of id => name for all cultures.
     *
     * @return array<string,string>
     */
    public function all(): array
    {
        $cultures = [];

        foreach (Culture::cases() as $case) {
            $cultures[$case->value] = $this->translator->trans($case->value, domain: 'cultures');
        }

        return $cultures;
    }
}
