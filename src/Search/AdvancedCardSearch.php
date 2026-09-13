<?php

declare(strict_types=1);

namespace App\Search;

use App\Entity\PublishedSet;
use App\Enum\Culture;
use App\Enum\Subtype;
use App\Enum\Type;

class AdvancedCardSearch
{
    private ?string $title = null;
    private ?Culture $culture = null;
    private ?int $twilightCost = null;
    private ?Type $type = null;
    private ?Subtype $subtype = null;
    private ?string $text = null;
    private ?PublishedSet $publishedSet = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getCulture(): ?Culture
    {
        return $this->culture;
    }

    public function setCulture(Culture $culture): self
    {
        $this->culture = $culture;

        return $this;
    }

    public function getTwilightCost(): ?int
    {
        return $this->twilightCost;
    }

    public function setTwilightCost(int $twilightCost): self
    {
        $this->twilightCost = $twilightCost;

        return $this;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    public function setType(Type $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getSubtype(): ?Subtype
    {
        return $this->subtype;
    }

    public function setSubtype(Subtype $subtype): self
    {
        $this->subtype = $subtype;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getPublishedSet(): ?PublishedSet
    {
        return $this->publishedSet;
    }

    public function setPublishedSet(?PublishedSet $publishedSet): self
    {
        $this->publishedSet = $publishedSet;

        return $this;
    }
}
