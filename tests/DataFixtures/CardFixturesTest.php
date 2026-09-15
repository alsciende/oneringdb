<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\DataFixtures\CardFixtures;
use App\DataFixtures\PublishedSetFixtures;
use App\Dto\DtoCard;
use App\Entity\CardTypes\CompanionCard;
use App\Entity\PublishedSet;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

#[CoversClass(CardFixtures::class)]
class CardFixturesTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/card-fixtures-test-' . uniqid();
        mkdir($this->projectDir . '/fixtures/cards', 0o777, true);
        file_put_contents($this->projectDir . '/fixtures/cards/card.json', '{}');
    }

    protected function tearDown(): void
    {
        unlink($this->projectDir . '/fixtures/cards/card.json');
        rmdir($this->projectDir . '/fixtures/cards');
        rmdir($this->projectDir . '/fixtures');
        rmdir($this->projectDir);
    }

    private function makeDto(): DtoCard
    {
        $dto = new DtoCard();
        $dto->setNumber = '1';
        $dto->cardNumber = '1';
        $dto->rarity = 'C';
        $dto->name = 'Aragorn';
        $dto->type = 'companion';
        $dto->unique = true;

        return $dto;
    }

    public function testLoadPersistsACardBuiltFromTheFixtureFile(): void
    {
        $publishedSet = new PublishedSet()->setId('01')->setName('Set')->setPosition(1);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('deserialize')->willReturn($this->makeDto());

        $repository = $this->createMock(ObjectRepository::class);
        $repository->method('find')->with('01')->willReturn($publishedSet);

        $manager = $this->createMock(ObjectManager::class);
        $manager->method('getRepository')->with(PublishedSet::class)->willReturn($repository);
        $manager->expects($this->once())->method('persist')->with($this->isInstanceOf(CompanionCard::class));
        $manager->expects($this->once())->method('flush');

        $fixtures = new CardFixtures($serializer, $this->projectDir);
        $fixtures->load($manager);
    }

    public function testLoadThrowsWhenDeserializationFails(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('deserialize')->willThrowException(new \RuntimeException('bad json'));

        $manager = $this->createMock(ObjectManager::class);
        $manager->method('getRepository')->willReturn($this->createMock(ObjectRepository::class));

        $fixtures = new CardFixtures($serializer, $this->projectDir);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Cannot deserialize/');
        $fixtures->load($manager);
    }

    public function testLoadThrowsWhenThePublishedSetIsMissing(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('deserialize')->willReturn($this->makeDto());

        $repository = $this->createMock(ObjectRepository::class);
        $repository->method('find')->willReturn(null);

        $manager = $this->createMock(ObjectManager::class);
        $manager->method('getRepository')->willReturn($repository);

        $fixtures = new CardFixtures($serializer, $this->projectDir);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Cannot find published set/');
        $fixtures->load($manager);
    }

    public function testLoadThrowsOnAnUnknownRarityCode(): void
    {
        $publishedSet = new PublishedSet()->setId('01')->setName('Set')->setPosition(1);
        $dto = $this->makeDto();
        $dto->rarity = 'Z';

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('deserialize')->willReturn($dto);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->method('find')->willReturn($publishedSet);

        $manager = $this->createMock(ObjectManager::class);
        $manager->method('getRepository')->willReturn($repository);

        $fixtures = new CardFixtures($serializer, $this->projectDir);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Unknown rarity code/');
        $fixtures->load($manager);
    }

    public function testGetDependenciesReturnsPublishedSetFixtures(): void
    {
        $fixtures = new CardFixtures($this->createMock(SerializerInterface::class), $this->projectDir);

        $this->assertSame([PublishedSetFixtures::class], $fixtures->getDependencies());
    }
}
