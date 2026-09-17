<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\AllyCardCrudController;
use App\Controller\Admin\ArtifactCardCrudController;
use App\Controller\Admin\ConditionCardCrudController;
use App\Controller\Admin\EventCardCrudController;
use App\Controller\Admin\FollowerCardCrudController;
use App\Controller\Admin\MinionCardCrudController;
use App\Controller\Admin\PossessionCardCrudController;
use App\Controller\Admin\RingCardCrudController;
use App\Controller\Admin\SiteCardCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Lightweight smoke test for the per-Type CRUD controllers not already covered in depth by
 * CompanionCardCrudControllerTest (which exercises AbstractCardCrudController's shared logic):
 * just checks each one's New page loads under its own title, with its own Type-specific fields.
 */
#[CoversClass(AllyCardCrudController::class)]
#[CoversClass(ArtifactCardCrudController::class)]
#[CoversClass(ConditionCardCrudController::class)]
#[CoversClass(EventCardCrudController::class)]
#[CoversClass(FollowerCardCrudController::class)]
#[CoversClass(MinionCardCrudController::class)]
#[CoversClass(PossessionCardCrudController::class)]
#[CoversClass(RingCardCrudController::class)]
#[CoversClass(SiteCardCrudController::class)]
class CardTypeCrudControllerSmokeTest extends WebTestCase
{
    /**
     * @return array<string, array{string, string, string, list<string>}>
     */
    public static function typeProvider(): array
    {
        return [
            'Ally' => ['ally-card', 'AllyCard', 'Ally', ['culture', 'twilightCost', 'strength', 'vitality', 'homeSite', 'subtype']],
            'Artifact' => ['artifact-card', 'ArtifactCard', 'Artifact', ['culture', 'twilightCost', 'strengthModifier', 'vitalityModifier', 'subtype']],
            'Condition' => ['condition-card', 'ConditionCard', 'Condition', ['culture', 'twilightCost', 'strengthModifier', 'vitalityModifier', 'siteNumberModifier', 'subtype']],
            'Event' => ['event-card', 'EventCard', 'Event', ['culture', 'twilightCost', 'subtype']],
            'Follower' => ['follower-card', 'FollowerCard', 'Follower', ['culture', 'twilightCost', 'strengthModifier', 'vitalityModifier', 'subtype']],
            'Minion' => ['minion-card', 'MinionCard', 'Minion', ['culture', 'twilightCost', 'strength', 'vitality', 'siteNumber', 'subtype']],
            'Possession' => ['possession-card', 'PossessionCard', 'Possession', ['culture', 'twilightCost', 'strengthModifier', 'vitalityModifier', 'subtype']],
            'Ring' => ['ring-card', 'RingCard', 'Ring', ['strengthModifier', 'vitalityModifier']],
            'Site' => ['site-card', 'SiteCard', 'Site', ['siteNumber', 'shadowNumber']],
        ];
    }

    /**
     * @param list<string> $expectedFields
     */
    #[DataProvider('typeProvider')]
    public function testNewPageShowsTheTitleAndTheExpectedFields(string $routeSegment, string $formName, string $typeLabel, array $expectedFields): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, sprintf('/admin/%s/new', $routeSegment));

        self::assertResponseIsSuccessful();
        self::assertSame(sprintf('Create %s Card', $typeLabel), $crawler->filter('h1.title')->text());

        // Fields common to every Type, and never absent from any of them.
        foreach ([...$expectedFields, 'title', 'position', 'publishedSet', 'rarity'] as $field) {
            self::assertGreaterThanOrEqual(
                1,
                $crawler->filter(sprintf('[name="%s[%s]"]', $formName, $field))->count(),
                sprintf('Missing "%s" field on the %s New page', $field, $typeLabel),
            );
        }
        // Multi-select association field, submitted as "rulesets[]".
        self::assertGreaterThanOrEqual(1, $crawler->filter(sprintf('[name="%s[rulesets][]"]', $formName))->count());

        // Fields that would only make sense for a *different* Type must not leak into this one.
        $allPossibleFields = ['culture', 'twilightCost', 'strength', 'vitality', 'strengthModifier', 'vitalityModifier', 'siteNumber', 'shadowNumber', 'signet', 'homeSite', 'siteNumberModifier', 'subtype'];
        foreach (array_diff($allPossibleFields, $expectedFields) as $field) {
            self::assertSame(
                0,
                $crawler->filter(sprintf('[name="%s[%s]"]', $formName, $field))->count(),
                sprintf('Unexpected "%s" field on the %s New page', $field, $typeLabel),
            );
        }
    }
}
