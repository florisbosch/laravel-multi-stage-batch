<?php

namespace Florisbosch\MultiStageBatch\Tests;

use Florisbosch\MultiStageBatch\MultiStageBatchServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            MultiStageBatchServiceProvider::class,
        ];
    }
}