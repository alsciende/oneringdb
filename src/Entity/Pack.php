<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PackRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Cache;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: PackRepository::class)]
#[Cache(usage: 'READ_ONLY')]
class Pack implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(type: Types::STRING, nullable: false)]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 5,nullable: false)]
    private string $shorthand;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    private string $name;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $size = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $release_date = null;

    /**
     * @var Collection<int, PackCard>
     */
    #[ORM\OneToMany(targetEntity: PackCard::class, mappedBy: 'pack')]
    #[Ignore]
    private Collection $packCards;

    public function __construct()
    {
        $this->packCards = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
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
        $this->release_date = $release_date;

        return $this;
    }

    public function getCardAt(int $i): ?PackCard
    {
        assert($i >= 0 && $i < $this->packCards->count(),
            sprintf(
                '%s: index out of range.  Highest index: %s',
                $i,
                $this->packCards->count() - 1
            ),
        );

        return $this->packCards->get($i);
    }

    /**
     * @return Collection<int, PackCard>
     */
    public function getPackCards(): Collection
    {
        return $this->packCards;
    }

    public function addCard(PackCard $card): static
    {
        if (! $this->packCards->contains($card)) {
            $this->packCards->add($card);
            $card->setPack($this);
        }

        return $this;
    }

    public function removeCard(PackCard $card): static
    {
        if ($this->packCards->removeElement($card)) {
            // set the owning side to null (unless already changed)
            if ($card->getPack() === $this) {
                $card->setPack(null);
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
            'name' => $this->getName(),
            'size' => $this->getSize(),
            'releaseDate' => $this->getReleaseDate(),
        ];
    }

    public function updateFrom(self $pack): void
    {
        $this->setId($pack->getId())
            ->setName($pack->getName())
            ->setSize($pack->getSize())
            ->setReleaseDate($pack->getReleaseDate())
        ;
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('%s (#%s)', $this->name, $this->shorthand);
    }
}
