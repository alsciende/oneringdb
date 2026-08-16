<?php

declare(strict_types=1);

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum Type: string implements TranslatableInterface
{
    case Ally = 'ally';
    case Artifact = 'artifact';
    case Companion = 'companion';
    case Condition = 'condition';
    case Event = 'event';
    case Minion = 'minion';
    case Possession = 'possession';
    case Ring = 'ring';
    case Site = 'site';
    case Follower = 'follower';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->value, domain: 'types', locale: $locale);
    }
}
