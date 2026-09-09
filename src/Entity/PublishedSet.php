<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PublishedSetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Cache;

#[ORM\Entity(repositoryClass: PublishedSetRepository::class)]
#[Cache(usage: 'READ_ONLY')]
class PublishedSet implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(type: Types::STRING, nullable: false)]
    private string $id;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private int $position;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    private ?string $shorthand = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    private string $name;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $size = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $release_date = null;

    /**
     * @var Collection<int, Card>
     */
    #[ORM\OneToMany(targetEntity: Card::class, mappedBy: 'publishedSet')]
    private Collection $cards;

    public function __construct()
    {
        $this->cards = new ArrayCollection();
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getShorthand(): ?string
    {
        return $this->shorthand;
    }

    public function setShorthand(?string $shorthand): static
    {
        $this->shorthand = $shorthand;

        return $this;
    }

    public function getReleaseDate(): ?\DateTimeInterface
    {
        return $this->release_date;
    }

    public function setReleaseDate(?\DateTimeInterface $release_date): static
    {
        $this->release_date = $release_date ? \DateTime::createFromInterface($release_date) : null;

        return $this;
    }

    public function getCardAt(int $i): ?Card
    {
        assert($i >= 0 && $i < $this->cards->count(),
            sprintf(
                '%s: index out of range.  Highest index: %s',
                $i,
                $this->cards->count() - 1
            ),
        );

        return $this->cards->get($i);
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
            $card->setPublishedSet($this);
        }

        return $this;
    }

    public function removeCard(Card $card): static
    {
        $this->cards->removeElement($card);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'position' => $this->getPosition(),
            'name' => $this->getName(),
            'size' => $this->getSize(),
            'releaseDate' => $this->getReleaseDate(),
        ];
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('%s (#%s)', $this->name, $this->shorthand);
    }
}
