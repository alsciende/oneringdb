<?php

declare(strict_types=1);

namespace App\Tests;

trait ProjectRoot
{
    public function getProjectRoot(): string
    {
        return dirname(__DIR__);
    }
}
