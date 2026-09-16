<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RulesetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Cache;

#[ORM\Entity(repositoryClass: RulesetRepository::class)]
#[Cache(usage: 'NONSTRICT_READ_WRITE')]
class Ruleset implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    private string $name;

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN, nullable: true, unique: true)]
    private ?bool $isActive = null;

    #[ORM\Column(name: 'publication_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $publicationDate = null;

    /**
     * @var Collection<int, Card>
     */
    #[ORM\ManyToMany(targetEntity: Card::class, mappedBy: 'rulesets')]
    #[Cache(usage: 'NONSTRICT_READ_WRITE')]
    private Collection $cards;

    public function __construct()
    {
        $this->cards = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive === true;
    }

    public function setActive(?bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getPublicationDate(): ?\DateTimeInterface
    {
        return $this->publicationDate;
    }

    public function setPublicationDate(?\DateTimeInterface $publicationDate): static
    {
        $this->publicationDate = $publicationDate !== null ? \DateTime::createFromInterface($publicationDate) : null;

        return $this;
    }

    /**
     * @return Collection<int, Card>
     */
    public function getCards(): Collection
    {
        return $this->cards;
    }

    public function addCard(Card $card): static
    {
        if (! $this->cards->contains($card)) {
            $this->cards->add($card);
            $card->addRuleset($this);
        }

        return $this;
    }

    public function removeCard(Card $card): static
    {
        if ($this->cards->removeElement($card)) {
            $card->removeRuleset($this);
        }

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->name;
    }
}
