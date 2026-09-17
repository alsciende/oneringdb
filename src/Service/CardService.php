<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Card;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Provides creation logic for Cards.
 */
class CardService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Duplicates a Card as a new revision: same content, but a new id (same card code, different
     * revision number) and no Ruleset links of its own. Defaults to the next revision after
     * $card's. The duplicate is not linked to any Ruleset — use RulesetService::swapCard() for that.
     */
    public function duplicate(Card $card, ?int $revision = null): Card
    {
        $revision ??= $card->getRevision() + 1;

        $entityClass = $card::class;
        $duplicate = new $entityClass();
        $duplicate->setId(Card::buildId($card->getPublishedSet(), $card->getPosition(), $revision));
        $duplicate->setRevision($revision);
        $duplicate->setPublishedSet($card->getPublishedSet());
        $duplicate->setTitle($card->getTitle());
        $duplicate->setSubtitle($card->getSubtitle());
        $duplicate->setCulture($card->getCulture());
        $duplicate->setTwilightCost($card->getTwilightCost());
        $duplicate->setText($card->getText());
        $duplicate->setPosition($card->getPosition());
        $duplicate->setLore($card->getLore());
        $duplicate->setImageUrl($card->getImageUrl());
        $duplicate->setRarity($card->getRarity());
        $duplicate->setUnique($card->isUnique());
        $duplicate->setSubtype($card->getSubtype());
        $duplicate->setStrength($card->getStrength());
        $duplicate->setVitality($card->getVitality());
        $duplicate->setStrengthModifier($card->getStrengthModifier());
        $duplicate->setVitalityModifier($card->getVitalityModifier());
        $duplicate->setSiteNumber($card->getSiteNumber());
        $duplicate->setShadowNumber($card->getShadowNumber());
        $duplicate->setSignet($card->getSignet());
        $duplicate->setHomeSite($card->getHomeSite());
        $duplicate->setSiteNumberModifier($card->getSiteNumberModifier());

        $this->entityManager->persist($duplicate);
        $this->entityManager->flush();

        return $duplicate;
    }
}
