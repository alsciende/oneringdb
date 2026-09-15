<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Entity\PublishedSet;
use App\Enum\Culture;
use App\Enum\Subtype;
use App\Enum\Type;
use App\Form\AdvancedCardSearchType;
use App\Search\AdvancedCardSearch;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

#[CoversClass(AdvancedCardSearchType::class)]
class AdvancedCardSearchTypeTest extends KernelTestCase
{
    public function testFormHasTheExpectedFields(): void
    {
        self::bootKernel();

        /** @var FormFactoryInterface $factory */
        $factory = static::getContainer()->get('form.factory');
        $form = $factory->create(AdvancedCardSearchType::class);

        foreach (['title', 'culture', 'twilight_cost', 'type', 'subtype', 'text', 'published_set', 'search'] as $field) {
            $this->assertTrue($form->has($field), "Expected the form to have a \"{$field}\" field.");
        }
    }

    public function testSubmittingValidDataPopulatesTheSearch(): void
    {
        self::bootKernel();

        /** @var FormFactoryInterface $factory */
        $factory = static::getContainer()->get('form.factory');
        $form = $factory->create(AdvancedCardSearchType::class, new AdvancedCardSearch(), [
            'csrf_protection' => false,
        ]);

        $form->submit([
            'title' => 'rosie',
            'culture' => Culture::Shire->value,
            'twilight_cost' => '4',
            'type' => Type::Ally->value,
            'subtype' => Subtype::Hobbit->value,
            'text' => 'pipeweed',
            'published_set' => '01',
        ]);

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());

        /** @var AdvancedCardSearch $search */
        $search = $form->getData();
        $this->assertSame('rosie', $search->getTitle());
        $this->assertSame(Culture::Shire, $search->getCulture());
        $this->assertSame(4, $search->getTwilightCost());
        $this->assertSame(Type::Ally, $search->getType());
        $this->assertSame(Subtype::Hobbit, $search->getSubtype());
        $this->assertSame('pipeweed', $search->getText());
        $this->assertInstanceOf(PublishedSet::class, $search->getPublishedSet());
        $this->assertSame('01', $search->getPublishedSet()->getId());
    }
}
