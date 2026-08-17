<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\{Culture, Type};
use App\Repository\CardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Cache;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: CardRepository::class)]
#[Cache(usage: 'READ_ONLY')]
#[ORM\UniqueConstraint(
    name: 'uniq_full_title',
    columns: ['title', 'subtitle'],
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

    #[ORM\Column('text', length: 1024, nullable: false)]
    private string $text;

    /**
     * @var Collection<int, PackCard>
     */
    #[ORM\OneToMany(targetEntity: PackCard::class, mappedBy: 'card', cascade: ['persist', 'remove'])]
    #[Ignore]
    private Collection $packCards;

    public function __construct()
    {
        $this->packCards = new ArrayCollection();
    }

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

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return Collection<int, PackCard>
     */
    public function getPackCards(): Collection
    {
        return $this->packCards;
    }

    public function addPackCard(PackCard $packCard): static
    {
        if (! $this->packCards->contains($packCard)) {
            $this->packCards->add($packCard);
            $packCard->setCard($this);
        }

        return $this;
    }

    public function removePackCard(PackCard $packCard): static
    {
        if ($this->packCards->removeElement($packCard)) {
            // set the owning side to null (unless already changed)
            if ($packCard->getCard() === $this) {
                $packCard->setCard(null);
            }
        }

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
        ];
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('%s (#%s)', $this->getFullTitle(), $this->id);
    }
}
