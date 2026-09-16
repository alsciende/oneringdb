<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\DataFixtures\PublishedSetFixtures;
use App\DataFixtures\RulesetFixtures;
use App\Dto\DtoCard;
use App\Entity\PublishedSet;
use App\Entity\Ruleset;
use App\Repository\PublishedSetRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

#[CoversClass(RulesetFixtures::class)]
class RulesetFixturesTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/ruleset-fixtures-test-' . uniqid();
        mkdir($this->projectDir . '/fixtures/rulesets', 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    /**
     * @param array<string, array<string, mixed>> $cards file name => card data (as it would appear in the real JSON)
     */
    private function makeRulesetDir(string $folderName, string $name, string $date, array $cards): void
    {
        $dir = "{$this->projectDir}/fixtures/rulesets/{$folderName}";
        mkdir("{$dir}/cards", 0o777, true);
        file_put_contents("{$dir}/ruleset.json", json_encode([
            'name' => $name,
            'date' => $date,
        ], JSON_THROW_ON_ERROR));

        foreach ($cards as $fileName => $data) {
            file_put_contents("{$dir}/cards/{$fileName}.json", json_encode($data, JSON_THROW_ON_ERROR));
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = "{$dir}/{$item}";
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }

    /**
     * A minimal stand-in for the real (Symfony) serializer: turns the same snake_case JSON shape
     * the real card fixture files use into a DtoCard, without re-testing Symfony's own denormalization.
     */
    private function fakeSerializer(): SerializerInterface&\PHPUnit\Framework\MockObject\MockObject
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('deserialize')->willReturnCallback(static function (string $json): DtoCard {
            /** @var array<string, mixed> $data */
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            foreach (['set_number', 'card_number', 'rarity', 'name', 'type'] as $requiredKey) {
                if (! isset($data[$requiredKey])) {
                    throw new \RuntimeException("Missing \"{$requiredKey}\" in test card fixture");
                }
            }

            $dto = new DtoCard();
            $dto->setNumber = (string) $data['set_number'];
            $dto->cardNumber = (string) $data['card_number'];
            $dto->rarity = (string) $data['rarity'];
            $dto->name = (string) $data['name'];
            $dto->type = (string) $data['type'];
            $dto->unique = (bool) ($data['unique'] ?? false);
            $dto->gameText = isset($data['game_text']) ? (string) $data['game_text'] : null;

            return $dto;
        });

        return $serializer;
    }

    /**
     * Mocks an EntityManager whose flush() assigns incrementing ids to persisted Rulesets
     * (simulating GeneratedValue id assignment) and whose Connection records every
     * executeStatement() call instead of actually running it. The returned recorder object is
     * mutated live by the mocks (a plain array return value would only be copied at call time,
     * before load() has run), so its properties reflect what actually happened after the fact.
     *
     * @return array{0: EntityManagerInterface, 1: object{persistedRulesets: list<Ruleset>, executeStatementCalls: list<array{sql: string, params: array<mixed>}>}}
     */
    private function makeManager(PublishedSet $publishedSet): array
    {
        $publishedSetRepository = $this->createMock(PublishedSetRepository::class);
        $publishedSetRepository->method('find')->willReturn($publishedSet);

        $recorder = new class {
            /**
             * @var list<Ruleset>
             */
            public array $persistedRulesets = [];

            /**
             * @var list<array{sql: string, params: array<mixed>}>
             */
            public array $executeStatementCalls = [];

            public int $nextRulesetId = 1;
        };

        $connection = $this->createMock(Connection::class);
        $connection->method('executeStatement')->willReturnCallback(
            function (string $sql, array $params = []) use ($recorder): int {
                $recorder->executeStatementCalls[] = [
                    'sql' => $sql,
                    'params' => $params,
                ];

                return intdiv(count($params), 2);
            }
        );

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->with(PublishedSet::class)->willReturn($publishedSetRepository);
        $manager->method('getConnection')->willReturn($connection);
        $manager->method('persist')->willReturnCallback(function (object $entity) use ($recorder): void {
            if ($entity instanceof Ruleset) {
                $recorder->persistedRulesets[] = $entity;
            }
        });
        $manager->method('flush')->willReturnCallback(function () use ($recorder): void {
            foreach ($recorder->persistedRulesets as $ruleset) {
                if ($ruleset->getId() === null) {
                    new \ReflectionProperty(Ruleset::class, 'id')->setValue($ruleset, $recorder->nextRulesetId++);
                }
            }
        });

        return [$manager, $recorder];
    }

    /**
     * @param array<mixed> $params
     *
     * @return array{cardIds: list<string>, rulesetId: int}
     */
    private function parseCardRulesetParams(array $params): array
    {
        $cardIds = [];
        $rulesetIds = [];
        for ($i = 0; $i < count($params); $i += 2) {
            $cardIds[] = $params[$i];
            $rulesetIds[] = $params[$i + 1];
        }

        sort($cardIds);
        $uniqueRulesetIds = array_unique($rulesetIds);
        $this->assertCount(1, $uniqueRulesetIds, 'A single bulk insert should target a single ruleset_id');

        return [
            'cardIds' => $cardIds,
            'rulesetId' => (int) reset($uniqueRulesetIds),
        ];
    }

    public function testLoadOrdersRulesetsByDateRegardlessOfFolderIteration(): void
    {
        $publishedSet = new PublishedSet()->setId('01')->setName('Set')->setPosition(1);

        $this->makeRulesetDir('z-newer', 'Errata Wave 1', '2004-01-01', [
            'card' => [
                'set_number' => '1',
                'card_number' => '1',
                'rarity' => 'C',
                'name' => 'Aragorn',
                'type' => 'companion',
                'unique' => true,
                'game_text' => 'Errata text',
            ],
        ]);
        $this->makeRulesetDir('a-older', 'Base Printing', '2003-01-01', [
            'card' => [
                'set_number' => '1',
                'card_number' => '1',
                'rarity' => 'C',
                'name' => 'Aragorn',
                'type' => 'companion',
                'unique' => true,
                'game_text' => 'Original text',
            ],
        ]);

        [$manager, $recorder] = $this->makeManager($publishedSet);

        $fixtures = new RulesetFixtures($this->fakeSerializer(), $this->projectDir);
        $fixtures->load($manager);

        $this->assertCount(2, $recorder->persistedRulesets);
        $this->assertSame('Base Printing', $recorder->persistedRulesets[0]->getName());
        $this->assertFalse($recorder->persistedRulesets[0]->isActive());
        $this->assertSame('Errata Wave 1', $recorder->persistedRulesets[1]->getName());
        $this->assertTrue($recorder->persistedRulesets[1]->isActive());
    }

    public function testLoadCarriesOverUnaffectedCardsAndBumpsTheRevisionOfErratedOnes(): void
    {
        $publishedSet = new PublishedSet()->setId('01')->setName('Set')->setPosition(1);

        $this->makeRulesetDir('2003-base', 'Base Printing', '2003-01-01', [
            'card-a' => [
                'set_number' => '1',
                'card_number' => '1',
                'rarity' => 'C',
                'name' => 'Aragorn',
                'type' => 'companion',
                'unique' => true,
                'game_text' => 'Original text',
            ],
            'card-b' => [
                'set_number' => '1',
                'card_number' => '2',
                'rarity' => 'C',
                'name' => 'Rosie',
                'type' => 'ally',
                'unique' => false,
            ],
        ]);
        $this->makeRulesetDir('2004-errata', 'Errata Wave 1', '2004-01-01', [
            'card-a' => [
                'set_number' => '1',
                'card_number' => '1',
                'rarity' => 'C',
                'name' => 'Aragorn',
                'type' => 'companion',
                'unique' => true,
                'game_text' => 'Errata text',
            ],
        ]);

        [$manager, $recorder] = $this->makeManager($publishedSet);

        $fixtures = new RulesetFixtures($this->fakeSerializer(), $this->projectDir);
        $fixtures->load($manager);

        $this->assertCount(2, $recorder->persistedRulesets);
        [$basePrinting, $errataWave1] = $recorder->persistedRulesets;

        $this->assertCount(2, $recorder->executeStatementCalls);
        $this->assertStringStartsWith('INSERT INTO card_ruleset', $recorder->executeStatementCalls[0]['sql']);

        $basePrintingInsert = $this->parseCardRulesetParams($recorder->executeStatementCalls[0]['params']);
        $this->assertSame(['01001.0', '01002.0'], $basePrintingInsert['cardIds']);
        $this->assertSame($basePrinting->getId(), $basePrintingInsert['rulesetId']);

        // The next Ruleset carries the untouched Card (Rosie) over and replaces the errated one.
        $errataWave1Insert = $this->parseCardRulesetParams($recorder->executeStatementCalls[1]['params']);
        $this->assertSame(['01001.1', '01002.0'], $errataWave1Insert['cardIds']);
        $this->assertSame($errataWave1->getId(), $errataWave1Insert['rulesetId']);
    }

    public function testLoadThrowsWhenDeserializationFails(): void
    {
        $this->makeRulesetDir('2003-base', 'Base Printing', '2003-01-01', [
            'card' => [
                'not' => 'a valid card shape, missing required keys',
            ],
        ]);

        [$manager] = $this->makeManager(new PublishedSet()->setId('01')->setName('Set')->setPosition(1));

        $fixtures = new RulesetFixtures($this->fakeSerializer(), $this->projectDir);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Cannot deserialize/');
        $fixtures->load($manager);
    }

    public function testLoadThrowsWhenThePublishedSetIsMissing(): void
    {
        $this->makeRulesetDir('2003-base', 'Base Printing', '2003-01-01', [
            'card' => [
                'set_number' => '1',
                'card_number' => '1',
                'rarity' => 'C',
                'name' => 'Aragorn',
                'type' => 'companion',
                'unique' => true,
            ],
        ]);

        $publishedSetRepository = $this->createMock(PublishedSetRepository::class);
        $publishedSetRepository->method('find')->willReturn(null);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($publishedSetRepository);
        $manager->method('getConnection')->willReturn($this->createMock(Connection::class));

        $fixtures = new RulesetFixtures($this->fakeSerializer(), $this->projectDir);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Cannot find published set/');
        $fixtures->load($manager);
    }

    public function testLoadThrowsOnAnUnknownRarityCode(): void
    {
        $this->makeRulesetDir('2003-base', 'Base Printing', '2003-01-01', [
            'card' => [
                'set_number' => '1',
                'card_number' => '1',
                'rarity' => 'Z',
                'name' => 'Aragorn',
                'type' => 'companion',
                'unique' => true,
            ],
        ]);

        [$manager] = $this->makeManager(new PublishedSet()->setId('01')->setName('Set')->setPosition(1));

        $fixtures = new RulesetFixtures($this->fakeSerializer(), $this->projectDir);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Unknown rarity code/');
        $fixtures->load($manager);
    }

    public function testLoadThrowsWhenARulesetJsonFileIsInvalid(): void
    {
        $dir = "{$this->projectDir}/fixtures/rulesets/broken";
        mkdir("{$dir}/cards", 0o777, true);
        file_put_contents("{$dir}/ruleset.json", 'not json');

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getConnection')->willReturn($this->createMock(Connection::class));

        $fixtures = new RulesetFixtures($this->fakeSerializer(), $this->projectDir);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Cannot parse/');
        $fixtures->load($manager);
    }

    public function testGetDependenciesReturnsPublishedSetFixtures(): void
    {
        $fixtures = new RulesetFixtures($this->fakeSerializer(), $this->projectDir);

        $this->assertSame([PublishedSetFixtures::class], $fixtures->getDependencies());
    }
}
