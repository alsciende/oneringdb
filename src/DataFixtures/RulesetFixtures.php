<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Dto\DtoCard;
use App\Entity\Card;
use App\Entity\PublishedSet;
use App\Entity\Ruleset;
use App\Enum\Culture;
use App\Enum\Rarity;
use App\Enum\Subtype;
use App\Enum\Type;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Loads every Ruleset under fixtures/rulesets/*\/ruleset.json (name + date), oldest first, and the
 * Cards in each Ruleset's cards/ folder. Only the first Ruleset's folder holds every Card; each
 * later Ruleset's folder holds only the Cards it introduces or revises, so its full Card set is
 * built by carrying over every Card from the previous Ruleset and replacing the ones a same-code
 * (same PublishedSet + position) file overrides with a new, incremented-revision Card. The most
 * recent Ruleset (by date) is the active one.
 */
class RulesetFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly string $projectDir,
    ) {
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        assert($manager instanceof EntityManagerInterface);
        $connection = $manager->getConnection();

        $publishedSetRepository = $manager->getRepository(PublishedSet::class);

        $rulesetDirs = $this->findRulesetDirsOrderedByDate();

        /**
         * Latest known revision of each Card, keyed by its PublishedSet+position code, carried
         * over from one Ruleset to the next.
         *
         * @var array<string, Card>
         */
        $currentCardByCode = [];

        foreach ($rulesetDirs as $index => $rulesetDir) {
            $ruleset = new Ruleset()
                ->setName($rulesetDir['name'])
                ->setPublicationDate(new \DateTime($rulesetDir['date']));

            if ($index === array_key_last($rulesetDirs)) {
                $ruleset->setActive(true);
            }

            $manager->persist($ruleset);

            $cardFinder = new Finder();
            $cardFinder->files()->in($rulesetDir['path'] . '/cards')->name('*.json');
            foreach ($cardFinder as $file) {
                try {
                    /** @var DtoCard $dto */
                    $dto = $this->serializer->deserialize($file->getContents(), DtoCard::class, 'json');
                } catch (\Exception $exception) {
                    throw new \RuntimeException("Cannot deserialize {$file}", previous: $exception);
                }

                $setId = sprintf('%02d', (int) $dto->setNumber);
                $publishedSet = $publishedSetRepository->find($setId);
                if ($publishedSet === null) {
                    throw new \RuntimeException("Cannot find published set {$setId} for {$file}");
                }

                $code = sprintf('%02d%03d', (int) $dto->setNumber, (int) $dto->cardNumber);
                $revision = isset($currentCardByCode[$code]) ? $currentCardByCode[$code]->getRevision() + 1 : 0;

                $card = $this->buildCard($dto, $publishedSet, $code, $revision, (string) $file);
                $manager->persist($card);

                $currentCardByCode[$code] = $card;
            }

            // Card and Ruleset rows must exist before card_ruleset can reference them.
            $manager->flush();

            // Bulk-inserted via DBAL rather than one addCard() per Card: with ~1839 Cards, going
            // through the ORM's ManyToMany persister means one INSERT per row (and, since
            // Card::$rulesets is L2C-cached, one cache write per Card too) — see CLAUDE.md.
            $this->bulkInsertCardRuleset($connection, $currentCardByCode, $ruleset->getId());
        }
    }

    /**
     * @param array<string, Card> $cardsByCode
     */
    private function bulkInsertCardRuleset(Connection $connection, array $cardsByCode, ?int $rulesetId): void
    {
        if ($cardsByCode === []) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($cardsByCode), '(?, ?)'));
        $params = [];
        foreach ($cardsByCode as $card) {
            $params[] = $card->getId();
            $params[] = $rulesetId;
        }

        $connection->executeStatement("INSERT INTO card_ruleset (card_id, ruleset_id) VALUES {$placeholders}", $params);
    }

    /**
     * @return list<array{path: string, name: string, date: string}>
     */
    private function findRulesetDirsOrderedByDate(): array
    {
        $dirFinder = new Finder();
        $dirFinder->directories()->in($this->projectDir . '/fixtures/rulesets/')->depth(0);

        $rulesetDirs = [];
        foreach ($dirFinder as $dir) {
            $rulesetFile = $dir->getPathname() . '/ruleset.json';

            try {
                /** @var array{name: string, date: string} $data */
                $data = json_decode((string) file_get_contents($rulesetFile), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new \RuntimeException("Cannot parse {$rulesetFile}", previous: $exception);
            }

            $rulesetDirs[] = [
                'path' => $dir->getPathname(),
                'name' => $data['name'],
                'date' => $data['date'],
            ];
        }

        usort($rulesetDirs, static fn (array $a, array $b): int => $a['date'] <=> $b['date']);

        return $rulesetDirs;
    }

    private function buildCard(DtoCard $dto, PublishedSet $publishedSet, string $code, int $revision, string $file): Card
    {
        $type = Type::from(strtolower($dto->type));
        $entityClass = Card::TYPE_ENTITY_CLASSES[$type->value];
        $card = new $entityClass();
        $card->setRevision($revision);
        $card->setId(sprintf('%s.%d', $code, $revision));
        $card->setPublishedSet($publishedSet);
        $card->setTitle($dto->name);
        $card->setSubtitle($dto->subtitle);
        $card->setCulture($dto->culture !== null ? Culture::from(strtolower($dto->culture)) : null);
        $card->setTwilightCost($dto->twilightCost !== null ? (int) $dto->twilightCost : null);
        $card->setText($dto->gameText);
        $card->setLore($dto->flavorText);
        $card->setPosition((int) $dto->cardNumber);
        $card->setImageUrl(sprintf('https://lotrtcgwiki.com/wiki/_media/cards:lotr%s.jpg', $code));
        $card->setRarity(Rarity::CODES[$dto->rarity] ?? throw new \RuntimeException("Unknown rarity code \"{$dto->rarity}\" in {$file}"));
        $card->setUnique($dto->unique);
        $card->setSubtype($dto->subtype !== null ? Subtype::from($dto->subtype) : null);
        $card->setStrength($dto->strength !== null ? (int) $dto->strength : null);
        $card->setVitality($dto->vitality !== null ? (int) $dto->vitality : null);
        $card->setStrengthModifier($dto->strengthModifier);
        $card->setVitalityModifier($dto->vitalityModifier);
        $card->setSiteNumber($dto->siteNumber !== null ? (int) $dto->siteNumber : null);
        $card->setShadowNumber($dto->shadowNumber !== null ? (int) $dto->shadowNumber : null);
        $card->setSignet($dto->signet);
        $card->setHomeSite($dto->homeSite);
        $card->setSiteNumberModifier($dto->siteNumberModifier);

        return $card;
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            PublishedSetFixtures::class,
        ];
    }
}
