<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\PublishedSet;
use App\Repository\PublishedSetRepository;
use App\Service\PublishedSetService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PublishedSetService::class)]
class PublishedSetServiceTest extends TestCase
{
    public function testAllReturnsAMapOfIdToName(): void
    {
        $set1 = new PublishedSet()->setId('01')->setName('The Fellowship of the Ring');
        $set2 = new PublishedSet()->setId('02')->setName('The Two Towers');

        $repository = $this->createMock(PublishedSetRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with([], [
                'id' => 'ASC',
            ])
            ->willReturn([$set1, $set2]);

        $service = new PublishedSetService($repository);

        $this->assertSame([
            '01' => 'The Fellowship of the Ring',
            '02' => 'The Two Towers',
        ], $service->all());
    }
}
