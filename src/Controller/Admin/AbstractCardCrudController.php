<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Card;
use App\Enum\Type;
use App\Service\RulesetService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Shared base for the per-Type Card CRUD controllers (RingCardCrudController, ...). These are used
 * only for the New/Edit pages: CardCrudController stays generic across all Card subtypes for
 * Index/Detail/Delete, and links here for creating/editing so each Type can eventually get its own
 * tailored field set. For now every subtype shares the same fields as CardCrudController; splitting
 * configureFields() per Type is a planned follow-up.
 *
 * @extends AbstractCrudController<Card>
 */
abstract class AbstractCardCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly RulesetService $rulesetService,
        private readonly TranslatorInterface $translator,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    abstract protected function getType(): Type;

    public function configureCrud(Crud $crud): Crud
    {
        $typeLabel = $this->getType()->trans($this->translator);

        return $crud
            ->setPageTitle(Crud::PAGE_EDIT, sprintf('Edit %s Card', $typeLabel))
            ->setPageTitle(Crud::PAGE_NEW, sprintf('Create %s Card', $typeLabel));
    }

    /**
     * Fields common to every Card Type, rendered first. Concrete controllers compose their own
     * configureFields() as [...commonFields(), <Type-specific fields>, ...trailingFields()].
     *
     * @return list<FieldInterface>
     */
    protected function commonFields(): array
    {
        return [
            BooleanField::new('unique')->hideOnIndex(),
            TextField::new('title')->hideOnIndex(),
            TextField::new('subtitle')->hideOnIndex(),
            TextField::new('type.value')
                ->setLabel('Type')
                ->onlyOnIndex()
                ->formatValue(fn (string $value): string => Type::from($value)->trans($this->translator)),
        ];
    }

    /**
     * Card::$culture is nullable at the DB level (Ring and Site Cards have none), so "required" is
     * enforced per-form here rather than as a constraint on the entity itself.
     */
    protected function requiredCultureField(): ChoiceField
    {
        return ChoiceField::new('culture')
            ->setRequired(true)
            ->setFormTypeOption('constraints', [new NotBlank()]);
    }

    /**
     * @return list<FieldInterface>
     */
    protected function trailingFields(): array
    {
        return [
            TextareaField::new('text')->hideOnIndex(),
            TextareaField::new('lore')->hideOnIndex(),
            AssociationField::new('publishedSet'),
            ChoiceField::new('rarity'),
            IntegerField::new('position'),
            AssociationField::new('rulesets')->hideOnIndex(),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        \assert($entityInstance instanceof Card);

        // A freshly created Card is always the first (revision 0) printing of its position; its id
        // is computed rather than user-entered (see commonFields(): there is no "id" field).
        $entityInstance->setRevision(0);
        $entityInstance->setId(Card::buildId($entityInstance->getPublishedSet(), $entityInstance->getPosition(), 0));

        $this->rulesetService->detachOtherRevisionsFromRulesets($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        \assert($entityInstance instanceof Card);
        $this->rulesetService->detachOtherRevisionsFromRulesets($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * @param AdminContext<Card> $context
     */
    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
        $submitButtonName = $context->getRequest()->request->all()['ea']['newForm']['btn'] ?? null;

        // "Save and return" would otherwise go back to *this* (per-Type) controller's own Index
        // page, which only lists Cards of this Type — send it to the generic CardCrudController's
        // Index instead, since that's the one used for browsing. "Save and continue editing" /
        // "Save and add another" stay on this controller, which is correct.
        if ($submitButtonName === Action::SAVE_AND_RETURN) {
            return $this->redirect(
                $this->adminUrlGenerator
                    ->setController(CardCrudController::class)
                    ->setAction(Action::INDEX)
                    ->generateUrl()
            );
        }

        return parent::getRedirectResponseAfterSave($context, $action);
    }
}
