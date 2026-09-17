<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Card;
use App\Enum\Type;
use App\Service\CardService;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\ActionGroup;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Index/Detail/Delete for Cards, across every Type. Create and Edit are handled by the per-Type
 * CRUD controllers instead (RingCardCrudController, ...; see AbstractCardCrudController), so this
 * controller's own New/Edit actions are unused: the "Add Card" dropdown and the "Edit" row action
 * both link out to the relevant per-Type controller.
 *
 * @extends AbstractCrudController<Card>
 */
class CardCrudController extends AbstractCrudController
{
    /**
     * @var array<string, class-string<AbstractCardCrudController>>
     */
    private const array TYPE_CRUD_CONTROLLERS = [
        Type::Ally->value => AllyCardCrudController::class,
        Type::Artifact->value => ArtifactCardCrudController::class,
        Type::Companion->value => CompanionCardCrudController::class,
        Type::Condition->value => ConditionCardCrudController::class,
        Type::Event->value => EventCardCrudController::class,
        Type::Minion->value => MinionCardCrudController::class,
        Type::Possession->value => PossessionCardCrudController::class,
        Type::Ring->value => RingCardCrudController::class,
        Type::Site->value => SiteCardCrudController::class,
        Type::Follower->value => FollowerCardCrudController::class,
    ];

    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Card::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort([
                'publishedSet' => 'ASC',
                'position' => 'ASC',
                'revision' => 'ASC',
            ]);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('fullTitle')->onlyOnIndex(),
            BooleanField::new('unique')->hideOnIndex(),
            TextField::new('title')->hideOnIndex(),
            TextField::new('subtitle')->hideOnIndex(),
            TextField::new('type.value')
                ->setLabel('Type')
                ->formatValue(fn (string $value): string => Type::from($value)->trans($this->translator)),
            ChoiceField::new('culture'),
            ChoiceField::new('rarity'),
            AssociationField::new('publishedSet'),
            TextareaField::new('text')->hideOnIndex(),
            TextareaField::new('lore')->hideOnIndex(),
            AssociationField::new('rulesets')->hideOnIndex(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $createRevision = Action::new('createRevision', 'Create Revision')
            ->linkToCrudAction('createRevision');

        $addCard = ActionGroup::new('addCard', 'Add Card')
            ->createAsGlobalActionGroup();

        foreach (Type::cases() as $type) {
            $addCard->addAction(
                Action::new('addCard_' . $type->value, $type)
                    ->linkToUrl(
                        $this->adminUrlGenerator
                            ->setController(self::TYPE_CRUD_CONTROLLERS[$type->value])
                            ->setAction(Action::NEW)
                            ->generateUrl()
                    )
            );
        }

        $linkToTypeCrudEdit = (fn (Card $card): string => $this->adminUrlGenerator
            ->setController(self::TYPE_CRUD_CONTROLLERS[$card->getType()->value])
            ->setAction(Action::EDIT)
            ->setEntityId($card->getId())
            ->generateUrl());

        return $actions
            ->add(Crud::PAGE_INDEX, $createRevision)
            ->add(Crud::PAGE_DETAIL, $createRevision)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->add(Crud::PAGE_INDEX, $addCard)
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action): Action => $action->linkToUrl($linkToTypeCrudEdit))
            ->update(Crud::PAGE_DETAIL, Action::EDIT, fn (Action $action): Action => $action->linkToUrl($linkToTypeCrudEdit));
    }

    /**
     * @param AdminContext<Card> $context
     */
    #[AdminRoute(path: '/{entityId}/create-revision', name: 'create_revision', options: [
        'methods' => ['GET'],
    ])]
    public function createRevision(AdminContext $context, CardService $cardService): RedirectResponse
    {
        $card = $context->getEntity()->getInstance();
        \assert($card instanceof Card);

        $revision = $cardService->duplicate($card);

        $url = $this->adminUrlGenerator
            ->setController(self::TYPE_CRUD_CONTROLLERS[$revision->getType()->value])
            ->setAction(Action::EDIT)
            ->setEntityId($revision->getId())
            ->generateUrl();

        return $this->redirect($url);
    }
}
