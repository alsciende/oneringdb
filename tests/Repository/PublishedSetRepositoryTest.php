<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Repository\PublishedSetRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(PublishedSetRepository::class)]
class PublishedSetRepositoryTest extends KernelTestCase
{
    public function testFindByPartialFieldMatchesNameCaseInsensitively(): void
    {
        self::bootKernel();

        /** @var PublishedSetRepository $repository */
        $repository = static::getContainer()->get(PublishedSetRepository::class);

        $result = $repository->findByPartialField([
            'name' => 'fellowship',
        ]);

        $this->assertCount(1, $result);
        $this->assertSame('01', $result[0]->getId());
    }

    public function testFindByPartialFieldMatchesAnExactField(): void
    {
        self::bootKernel();

        /** @var PublishedSetRepository $repository */
        $repository = static::getContainer()->get(PublishedSetRepository::class);

        $result = $repository->findByPartialField([
            'id' => '01',
        ]);

        $this->assertCount(1, $result);
        $this->assertSame('01', $result[0]->getId());
    }

    public function testFindByPartialFieldReturnsEmptyArrayWhenNothingMatches(): void
    {
        self::bootKernel();

        /** @var PublishedSetRepository $repository */
        $repository = static::getContainer()->get(PublishedSetRepository::class);

        $result = $repository->findByPartialField([
            'name' => 'xxxxxxxxxx',
        ]);

        $this->assertSame([], $result);
    }
}
