<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\SimpleCardSearchType;
use App\Search\SimpleCardSearch;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

#[CoversClass(SimpleCardSearchType::class)]
class SimpleCardSearchTypeTest extends KernelTestCase
{
    public function testFormHasTheExpectedFields(): void
    {
        self::bootKernel();

        /** @var FormFactoryInterface $factory */
        $factory = static::getContainer()->get('form.factory');
        $form = $factory->create(SimpleCardSearchType::class);

        $this->assertTrue($form->has('q'));
        $this->assertTrue($form->has('view'));
        $this->assertTrue($form->has('sort'));
        $this->assertTrue($form->has('page'));
        $this->assertSame('GET', $form->getConfig()->getMethod());
        $this->assertFalse($form->getConfig()->getOption('csrf_protection'));
    }

    public function testGetBlockPrefixIsEmpty(): void
    {
        $this->assertSame('', new SimpleCardSearchType()->getBlockPrefix());
    }

    public function testSubmittingValidDataPopulatesTheSearch(): void
    {
        self::bootKernel();

        /** @var FormFactoryInterface $factory */
        $factory = static::getContainer()->get('form.factory');
        $form = $factory->create(SimpleCardSearchType::class, new SimpleCardSearch());

        $form->submit([
            'q' => 'rosie',
            'view' => 'text',
            'sort' => 'title',
            'page' => '2',
        ]);

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());

        /** @var SimpleCardSearch $search */
        $search = $form->getData();
        $this->assertSame('rosie', $search->q);
        $this->assertSame('text', $search->view);
        $this->assertSame('title', $search->sort);
        $this->assertSame(2, $search->page);
    }
}
