<?php

declare(strict_types=1);

namespace App\Tests\Search;

use App\Search\SimpleCardSearch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SimpleCardSearch::class)]
class SimpleCardSearchTest extends TestCase
{
    public function testDefaults(): void
    {
        $search = new SimpleCardSearch();

        $this->assertSame('', $search->q);
        $this->assertSame('table', $search->view);
        $this->assertSame('position', $search->sort);
        $this->assertSame(1, $search->page);
    }

    public function testToArray(): void
    {
        $search = new SimpleCardSearch('rosie');
        $search->view = 'text';
        $search->sort = 'title';
        $search->page = 2;

        $this->assertSame([
            'q' => 'rosie',
            'view' => 'text',
            'sort' => 'title',
            'page' => 2,
        ], $search->toArray());
    }
}
