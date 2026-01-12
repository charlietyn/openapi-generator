<?php

namespace Ronu\OpenApiGenerator\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Mockery;
use Ronu\OpenApiGenerator\Services\OpenApiServices;
use Ronu\OpenApiGenerator\Tests\TestCase;

class OpenApiGeneratorTest extends TestCase
{
    public function test_service_provider_registers_openapi_service(): void
    {
        $this->assertTrue($this->app->bound(OpenApiServices::class));
    }

    public function test_routes_are_registered(): void
    {
        $routes = Route::getRoutes();

        $this->assertNotNull($routes->getByName('openapi.spec'));
        $this->assertNotNull($routes->getByName('openapi.postman'));
        $this->assertNotNull($routes->getByName('openapi.insomnia'));
    }

    public function test_spec_generation_endpoint_returns_json(): void
    {
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => new \stdClass(),
        ];

        $mock = Mockery::mock(OpenApiServices::class);
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn($spec);
        $mock->shouldReceive('generateFilename')
            ->once()
            ->andReturn('openapi.json');

        $this->app->instance(OpenApiServices::class, $mock);

        $response = $this->get('/documentation/openapi.json');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJson([
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
        ]);
    }
}
