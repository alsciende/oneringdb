<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Card;
use Symfony\Component\Console\Style\SymfonyStyle;

trait CardTableTrait
{
    abstract public function getStyle(): SymfonyStyle;

    /**
     * @param array<Card> $cards
     */
    public function printCards(array $cards): void
    {
        $this->getStyle()->table(
            $this->getCardHeaders(),
            array_map(fn (Card $card): array => [
                $card->getTitle(),
                $card->getId(),
                $card->getCulture()?->value,
                $card->getType()->value,
            ], $cards),
        );
        $this->getStyle()->comment(count($cards) . ' cards');
    }

    /**
     * @return string[]
     */
    private function getCardHeaders(): array
    {
        return [
            'title',
            'id',
            'culture',
            'type',
        ];
    }
}
