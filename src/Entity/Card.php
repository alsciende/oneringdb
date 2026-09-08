<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\{Culture, Rarity, Type};
use App\Repository\CardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Cache;

#[ORM\Entity(repositoryClass: CardRepository::class)]
#[Cache(usage: 'READ_ONLY')]
#[ORM\UniqueConstraint(
    name: 'uniq_published_set_position',
    columns: ['published_set_id', 'position'],
)]
class Card implements \Stringable
{
    #[ORM\Id]
    #[ORM\Column(length: 50, nullable: false)]
    private string $id;

    #[ORM\Column(length: 50, nullable: false)]
    private string $title;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $subtitle = null;

    #[ORM\Column(type: Types::STRING, enumType: Culture::class, nullable: true)]
    private ?Culture $culture = null;

    #[ORM\Column(nullable: true)]
    private ?int $twilightCost = null;

    #[ORM\Column(type: Types::STRING, enumType: Type::class, nullable: false)]
    private Type $type;

    #[ORM\Column('text', length: 1024, nullable: true)]
    private ?string $text = null;

    #[ORM\ManyToOne(targetEntity: PublishedSet::class, inversedBy: 'cards')]
    #[ORM\JoinColumn(name: 'published_set_id', referencedColumnName: 'id', nullable: false)]
    private PublishedSet $publishedSet;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $position = null;

    #[ORM\Column(length: 1023, nullable: true)]
    private ?string $lore = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: Types::STRING, enumType: Rarity::class, nullable: false)]
    private Rarity $rarity;

    #[ORM\Column(name: 'is_unique', type: Types::BOOLEAN, nullable: false)]
    private bool $unique;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $subtype = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $strength = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $vitality = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $strengthModifier = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $vitalityModifier = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $siteNumber = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $shadowNumber = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $signet = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $homeSite = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $siteNumberModifier = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): static
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function getFullTitle(): string
    {
        if (! is_string($this->subtitle)) {
            return $this->title;
        }

        return sprintf('%s, %s', $this->title, $this->subtitle);
    }

    public function getCulture(): ?Culture
    {
        return $this->culture;
    }

    public function setCulture(?Culture $culture = null): static
    {
        $this->culture = $culture;

        return $this;
    }

    public function getTwilightCost(): ?int
    {
        return $this->twilightCost;
    }

    public function setTwilightCost(?int $twilightCost): static
    {
        $this->twilightCost = $twilightCost;

        return $this;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function setType(Type $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getPublishedSet(): PublishedSet
    {
        return $this->publishedSet;
    }

    public function setPublishedSet(PublishedSet $publishedSet): static
    {
        $this->publishedSet = $publishedSet;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getLore(): ?string
    {
        return $this->lore;
    }

    public function setLore(?string $lore): static
    {
        $this->lore = $lore;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getRarity(): Rarity
    {
        return $this->rarity;
    }

    public function setRarity(Rarity $rarity): static
    {
        $this->rarity = $rarity;

        return $this;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function setUnique(bool $unique): static
    {
        $this->unique = $unique;

        return $this;
    }

    public function getSubtype(): ?string
    {
        return $this->subtype;
    }

    public function setSubtype(?string $subtype): static
    {
        $this->subtype = $subtype;

        return $this;
    }

    public function getStrength(): ?int
    {
        return $this->strength;
    }

    public function setStrength(?int $strength): static
    {
        $this->strength = $strength;

        return $this;
    }

    public function getVitality(): ?int
    {
        return $this->vitality;
    }

    public function setVitality(?int $vitality): static
    {
        $this->vitality = $vitality;

        return $this;
    }

    public function getStrengthModifier(): ?string
    {
        return $this->strengthModifier;
    }

    public function setStrengthModifier(?string $strengthModifier): static
    {
        $this->strengthModifier = $strengthModifier;

        return $this;
    }

    public function getVitalityModifier(): ?string
    {
        return $this->vitalityModifier;
    }

    public function setVitalityModifier(?string $vitalityModifier): static
    {
        $this->vitalityModifier = $vitalityModifier;

        return $this;
    }

    public function getSiteNumber(): ?int
    {
        return $this->siteNumber;
    }

    public function setSiteNumber(?int $siteNumber): static
    {
        $this->siteNumber = $siteNumber;

        return $this;
    }

    public function getShadowNumber(): ?int
    {
        return $this->shadowNumber;
    }

    public function setShadowNumber(?int $shadowNumber): static
    {
        $this->shadowNumber = $shadowNumber;

        return $this;
    }

    public function getSignet(): ?string
    {
        return $this->signet;
    }

    public function setSignet(?string $signet): static
    {
        $this->signet = $signet;

        return $this;
    }

    public function getHomeSite(): ?string
    {
        return $this->homeSite;
    }

    public function setHomeSite(?string $homeSite): static
    {
        $this->homeSite = $homeSite;

        return $this;
    }

    public function getSiteNumberModifier(): ?string
    {
        return $this->siteNumberModifier;
    }

    public function setSiteNumberModifier(?string $siteNumberModifier): static
    {
        $this->siteNumberModifier = $siteNumberModifier;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'subtitle' => $this->getSubtitle(),
            'culture' => $this->getCulture()?->value,
            'twilight_cost' => $this->getTwilightCost(),
            'type' => $this->getType()->value,
            'text' => $this->getText(),
            'published_set_id' => $this->getPublishedSet()->getId(),
            'position' => $this->getPosition(),
            'lore' => $this->getLore(),
            'image_url' => $this->getImageUrl(),
            'rarity' => $this->getRarity()->value,
            'unique' => $this->isUnique(),
            'subtype' => $this->getSubtype(),
            'strength' => $this->getStrength(),
            'vitality' => $this->getVitality(),
            'strength_modifier' => $this->getStrengthModifier(),
            'vitality_modifier' => $this->getVitalityModifier(),
            'site_number' => $this->getSiteNumber(),
            'shadow_number' => $this->getShadowNumber(),
            'signet' => $this->getSignet(),
            'home_site' => $this->getHomeSite(),
            'site_number_modifier' => $this->getSiteNumberModifier(),
        ];
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('%s (#%s)', $this->getFullTitle(), $this->id);
    }
}
