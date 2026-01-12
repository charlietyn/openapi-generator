<?php

namespace Ronu\OpenApiGenerator\Tests;

use Mockery;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Ronu\OpenApiGenerator\Providers\OpenApiGeneratorServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [OpenApiGeneratorServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('openapi.routes.enabled', true);
        $app['config']->set('openapi.routes.prefix', 'documentation');
        $app['config']->set('openapi.routes.middleware', []);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
