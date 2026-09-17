<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\CardCrudController;
use App\Entity\Card;
use App\Enum\Type;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(CardCrudController::class)]
class CardCrudControllerTest extends WebTestCase
{
    /**
     * testCreateRevisionRedirectsToTheNewRevisionsEditPage() persists a real row outside of any
     * rolled-back transaction (this project has no transactional test isolation), so clean it up.
     */
    protected function tearDown(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->executeStatement("DELETE FROM card WHERE id = '01001.1'");
        $entityManager->getCache()?->evictCollectionRegion(Card::class, 'rulesets');

        parent::tearDown();
    }

    public function testIndexPageListsCards(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/admin/card');

        self::assertResponseIsSuccessful();
    }

    public function testDetailPageShowsACard(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/admin/card/01007.0');

        self::assertResponseIsSuccessful();
    }

    public function testIndexPageAddCardDropdownListsEveryType(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/admin/card');

        foreach (Type::cases() as $type) {
            $links = $crawler->filter(sprintf('a[data-action-name="addCard_%s"]', $type->value));
            self::assertCount(1, $links, sprintf('Missing "Add Card" entry for Type "%s"', $type->value));
            self::assertStringContainsString(sprintf('/admin/%s-card/new', $type->value), (string) $links->attr('href'));
        }
    }

    public function testIndexPageEditLinkPointsToTheCardsOwnTypeCrudController(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/admin/card');

        // 01001.0 is a Ring, 01007.0 is a Companion: their "Edit" row action must each link to
        // their own per-Type CRUD controller, not to CardCrudController's own (unused) Edit action.
        $ringEditLink = $crawler->filter('a[href$="/admin/ring-card/01001.0/edit"]');
        self::assertGreaterThanOrEqual(1, $ringEditLink->count());

        $companionEditLink = $crawler->filter('a[href$="/admin/companion-card/01007.0/edit"]');
        self::assertGreaterThanOrEqual(1, $companionEditLink->count());
    }

    public function testDetailPageEditLinkPointsToTheCardsOwnTypeCrudController(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/admin/card/01007.0');

        $editLink = $crawler->filter('a[href$="/admin/companion-card/01007.0/edit"]');
        self::assertGreaterThanOrEqual(1, $editLink->count());
    }

    public function testCreateRevisionRedirectsToTheNewRevisionsEditPage(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/admin/card/01001.0/create-revision');

        self::assertResponseRedirects('http://localhost/admin/ring-card/01001.1/edit');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $revision = $entityManager->find(Card::class, '01001.1');

        self::assertNotNull($revision);
        self::assertSame(1, $revision->getRevision());
        self::assertSame(Type::Ring, $revision->getType());
        self::assertCount(0, $revision->getRulesets());
    }
}
