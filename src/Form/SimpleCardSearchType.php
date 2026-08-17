<?php

declare(strict_types=1);

namespace App\Form;

use App\Search\SimpleCardSearch;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<SimpleCardSearch>
 */
class SimpleCardSearchType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('q', SearchType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'card_search_input_placeholder',
                    'aria-label' => 'Search',
                    'size' => '25',
                    'autocorrect' => 'off',
                    'spellcheck' => 'false',
                    'autofocus' => 'on',
                ],
                'empty_data' => '',
            ])
            ->add('view', ChoiceType::class, [
                'choices' => [
                    'fa-list' => 'table',
                    'fa-th' => 'text',
                    'fa-file-image-o' => 'image',
                    'fa-id-card-o' => 'full',
                ],
                'empty_data' => 'table',
            ])
            ->add('sort', ChoiceType::class, [
                'choices' => [
                    'card_search_sort_title' => 'title',
                    'card_search_sort_culture' => 'culture',
                    'card_search_sort_type' => 'type',
                    'card_search_sort_twilight_cost' => 'twilightCost',
                    'card_search_sort_position' => 'position',
                ],
                'empty_data' => 'title',
            ])
            ->add('page', HiddenType::class, [
                'empty_data' => '1',
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'role' => 'search',
            ],
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return '';
    }
}
