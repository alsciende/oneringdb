<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\CardTypes\{AllyCard, ArtifactCard, CompanionCard, ConditionCard, EventCard, FollowerCard, MinionCard, PossessionCard, RingCard, SiteCard};
use App\Enum\{Culture, Rarity, Type};
use App\Repository\CardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Cache;

#[ORM\Entity(repositoryClass: CardRepository::class)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(self::TYPE_ENTITY_CLASSES)]
#[Cache(usage: 'READ_ONLY')]
#[ORM\UniqueConstraint(
    name: 'uniq_published_set_position',
    columns: ['published_set_id', 'position'],
)]
abstract class Card implements \Stringable
{
    /**
     * Single source of truth for the Type <-> concrete Card subclass mapping, reused by the
     * discriminator map above and by any code that needs to go from a Type to its entity class
     * (e.g. TypeSearchQueryBuilder, CardFixtures).
     *
     * @var array<string, class-string<Card>>
     */
    public const array TYPE_ENTITY_CLASSES = [
        Type::Ally->value => AllyCard::class,
        Type::Artifact->value => ArtifactCard::class,
        Type::Companion->value => CompanionCard::class,
        Type::Condition->value => ConditionCard::class,
        Type::Event->value => EventCard::class,
        Type::Minion->value => MinionCard::class,
        Type::Possession->value => PossessionCard::class,
        Type::Ring->value => RingCard::class,
        Type::Site->value => SiteCard::class,
        Type::Follower->value => FollowerCard::class,
    ];

    private const string UNIQUE_SYMBOL = '•';

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
        $title = $this->unique ? sprintf('%s%s', self::UNIQUE_SYMBOL, $this->title) : $this->title;

        if (! is_string($this->subtitle)) {
            return $title;
        }

        return sprintf('%s, %s', $title, $this->subtitle);
    }

    /**
     * Overridden by the concrete Card subtypes that actually have a culture.
     */
    public function hasCulture(): bool
    {
        return false;
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

    /**
     * Overridden by the concrete Card subtypes that actually have a twilight cost.
     */
    public function hasTwilightCost(): bool
    {
        return false;
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

    abstract public function getType(): Type;

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

    /**
     * Overridden by the concrete Card subtypes that actually have a strength.
     */
    public function hasStrength(): bool
    {
        return false;
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

    /**
     * Overridden by the concrete Card subtypes that actually have a vitality.
     */
    public function hasVitality(): bool
    {
        return false;
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

    /**
     * Overridden by the concrete Card subtypes that actually have a strength modifier.
     */
    public function hasStrengthModifier(): bool
    {
        return false;
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

    /**
     * Overridden by the concrete Card subtypes that actually have a vitality modifier.
     */
    public function hasVitalityModifier(): bool
    {
        return false;
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

    /**
     * Overridden by the concrete Card subtypes that actually have a site number.
     */
    public function hasSiteNumber(): bool
    {
        return false;
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

    /**
     * Overridden by the concrete Card subtypes that actually have a shadow number.
     */
    public function hasShadowNumber(): bool
    {
        return false;
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

    /**
     * Overridden by the concrete Card subtypes that actually have a signet.
     */
    public function hasSignet(): bool
    {
        return false;
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

    /**
     * Overridden by the concrete Card subtypes that actually have a home site.
     */
    public function hasHomeSite(): bool
    {
        return false;
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

    /**
     * Overridden by the concrete Card subtypes that actually have a site number modifier.
     */
    public function hasSiteNumberModifier(): bool
    {
        return false;
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
     * The template used to render this card as a row in a card list. Overridden by concrete
     * Card subtypes that need a different layout.
     */
    public function getRowTemplate(): string
    {
        return 'component/card_list/_row_default.html.twig';
    }

    /**
     * The template used to render this card's details in a text-mode card list. Overridden by
     * concrete Card subtypes that need a different layout.
     */
    public function getTextTemplate(): string
    {
        return 'component/_card_text.html.twig';
    }

    public function getCollectorInfo(): string
    {
        return sprintf("%s\u{202F}%s\u{202F}%s", $this->getPublishedSet()->getPosition(), $this->getRarity()->getCode(), $this->getPosition());
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
