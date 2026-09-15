<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\DataFixtures\PublishedSetFixtures;
use App\Entity\PublishedSet;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

#[CoversClass(PublishedSetFixtures::class)]
class PublishedSetFixturesTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/published-set-fixtures-test-' . uniqid();
        mkdir($this->projectDir . '/fixtures/published_sets', 0o777, true);
        file_put_contents($this->projectDir . '/fixtures/published_sets/set.json', '{}');
    }

    protected function tearDown(): void
    {
        unlink($this->projectDir . '/fixtures/published_sets/set.json');
        rmdir($this->projectDir . '/fixtures/published_sets');
        rmdir($this->projectDir . '/fixtures');
        rmdir($this->projectDir);
    }

    public function testLoadPersistsEachDeserializedPublishedSet(): void
    {
        $publishedSet = new PublishedSet()->setId('01')->setName('Set')->setPosition(1);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('deserialize')->willReturn($publishedSet);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())->method('persist')->with($publishedSet);
        $manager->expects($this->once())->method('flush');

        $fixtures = new PublishedSetFixtures($serializer, $this->projectDir);
        $fixtures->load($manager);
    }
}
