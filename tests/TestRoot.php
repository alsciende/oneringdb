<?php

declare(strict_types=1);

namespace App\Tests;

trait TestRoot
{
    public function getTestRoot(): string
    {
        return __DIR__;
    }
}
