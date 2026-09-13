<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\PublishedSet;
use App\Enum\Culture;
use App\Enum\Subtype;
use App\Enum\Type;
use App\Search\AdvancedCardSearch;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<AdvancedCardSearch>
 */
class AdvancedCardSearchType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => false,
                'label' => 'card_search_title_input_label',
            ])
            ->add('culture', EnumType::class, [
                'class' => Culture::class,
                'placeholder' => 'card_search_culture_input_placeholder',
                'required' => false,
                'label' => 'card_search_culture_input_label',
            ])
            ->add('twilight_cost', IntegerType::class, [
                'required' => false,
                'label' => 'card_search_twilight_cost_input_label',
            ])
            ->add('type', EnumType::class, [
                'class' => Type::class,
                'placeholder' => 'card_search_type_input_placeholder',
                'required' => false,
                'label' => 'card_search_type_input_label',
            ])
            ->add('subtype', EnumType::class, [
                'class' => Subtype::class,
                'placeholder' => 'card_search_subtype_input_placeholder',
                'required' => false,
                'label' => 'card_search_subtype_input_label',
            ])
            ->add('text', TextType::class, [
                'required' => false,
                'label' => 'card_search_text_input_label',
            ])
            ->add('published_set', EntityType::class, [
                'class' => PublishedSet::class,
                'query_builder' => fn (EntityRepository $er): QueryBuilder => $er->createQueryBuilder('p')->setCacheable(true),
                'placeholder' => 'card_search_published_set_input_placeholder',
                'required' => false,
                'label' => 'card_search_published_set_input_label',
            ])
            ->add('search', SubmitType::class, [
                'label' => 'card_search_search_button_label',
            ]);
    }
}
