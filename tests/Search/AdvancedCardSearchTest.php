<?php

declare(strict_types=1);

namespace App\Tests\Search;

use App\Entity\PublishedSet;
use App\Enum\Culture;
use App\Enum\Subtype;
use App\Enum\Type;
use App\Search\AdvancedCardSearch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdvancedCardSearch::class)]
class AdvancedCardSearchTest extends TestCase
{
    public function testDefaultsAreAllNull(): void
    {
        $search = new AdvancedCardSearch();

        $this->assertNull($search->getTitle());
        $this->assertNull($search->getCulture());
        $this->assertNull($search->getTwilightCost());
        $this->assertNull($search->getType());
        $this->assertNull($search->getSubtype());
        $this->assertNull($search->getText());
        $this->assertNull($search->getPublishedSet());
    }

    public function testGettersAndSetters(): void
    {
        $publishedSet = new PublishedSet()->setId('01');

        $search = new AdvancedCardSearch()
            ->setTitle('rosie')
            ->setCulture(Culture::Shire)
            ->setTwilightCost(4)
            ->setType(Type::Ally)
            ->setSubtype(Subtype::Hobbit)
            ->setText('pipeweed')
            ->setPublishedSet($publishedSet);

        $this->assertSame('rosie', $search->getTitle());
        $this->assertSame(Culture::Shire, $search->getCulture());
        $this->assertSame(4, $search->getTwilightCost());
        $this->assertSame(Type::Ally, $search->getType());
        $this->assertSame(Subtype::Hobbit, $search->getSubtype());
        $this->assertSame('pipeweed', $search->getText());
        $this->assertSame($publishedSet, $search->getPublishedSet());
    }
}
