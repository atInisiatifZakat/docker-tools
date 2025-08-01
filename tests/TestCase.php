<?php

declare(strict_types=1);

namespace Inisiatif\DockerTools\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Inisiatif\DockerTools\DockerToolsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            DockerToolsServiceProvider::class,
        ];
    }
}
