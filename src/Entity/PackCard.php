<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PackCardRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Cache;

#[ORM\Entity(repositoryClass: PackCardRepository::class)]
#[Cache(usage: 'READ_ONLY')]
#[ORM\UniqueConstraint(
    name: 'uniq_pack_card',
    columns: ['pack_id', 'card_id'],
)]
#[ORM\UniqueConstraint(
    name: 'uniq_pack_position',
    columns: ['pack_id', 'position'],
)]
class PackCard implements \Stringable
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Card::class, inversedBy: 'packCards')]
    #[ORM\JoinColumn(name: 'card_id', referencedColumnName: 'id')]
    private ?Card $card = null;

    #[ORM\ManyToOne(targetEntity: Pack::class, inversedBy: 'packCards')]
    #[ORM\JoinColumn(name: 'pack_id', referencedColumnName: 'id')]
    private ?Pack $pack = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $quantity = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $position = null;

    #[ORM\Column(length: 1023, nullable: true)]
    private ?string $lore = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image_url = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

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
        return $this->image_url;
    }

    public function setImageUrl(?string $image_url): static
    {
        $this->image_url = $image_url;

        return $this;
    }

    public function getCard(): ?Card
    {
        return $this->card;
    }

    public function setCard(?Card $card): static
    {
        $this->card = $card;

        return $this;
    }

    public function getPack(): ?Pack
    {
        return $this->pack;
    }

    public function setPack(?Pack $pack): static
    {
        $this->pack = $pack;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'card_id' => $this->card->getId(),
            'pack_id' => $this->pack->getId(),
            'quantity' => $this->quantity,
            'position' => $this->position,
            'lore' => $this->lore,
            'image_url' => $this->image_url,
        ];
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('%s (#%s) x %s (#%s)', $this->card->getFullTitle(), $this->card->getId(), $this->pack->getName(), $this->pack->getId());
    }
}
