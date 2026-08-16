<?php

declare(strict_types=1);

namespace App\Tests\SearchQueryBuilder;

use App\SearchQueryBuilder\UniqueIdentifierGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UniqueIdentifierGenerator::class)]
class UniqueIdentifierGeneratorTest extends TestCase
{
    public function testSomething(): void
    {
        $generator = new UniqueIdentifierGenerator();

        $this->assertEquals('name0', $generator->generateIdentifier('name'));
        $this->assertEquals('name1', $generator->generateIdentifier('name'));
        $this->assertEquals('type0', $generator->generateIdentifier('type'));
    }
}
