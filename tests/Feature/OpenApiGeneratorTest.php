<?php

namespace Ronu\OpenApiGenerator\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Mockery;
use Ronu\OpenApiGenerator\Services\InsomniaWorkspaceGenerator;
use Ronu\OpenApiGenerator\Services\OpenApiServices;
use Ronu\OpenApiGenerator\Services\PostmanCollectionGenerator;
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

    public function test_generate_passes_normalized_api_types_to_collection_generators(): void
    {
        $this->app['config']->set('openapi.cache.enabled', false);

        $postmanGenerator = Mockery::mock(PostmanCollectionGenerator::class);
        $insomniaGenerator = Mockery::mock(InsomniaWorkspaceGenerator::class);

        $postmanGenerator->shouldReceive('generate')
            ->once()
            ->with(Mockery::type('array'), 'artisan', ['mobile'])
            ->andReturn(['postman' => true]);

        $insomniaGenerator->shouldReceive('generate')
            ->once()
            ->with(Mockery::type('array'), 'artisan', ['mobile'])
            ->andReturn(['insomnia' => true]);

        $service = new class($postmanGenerator, $insomniaGenerator) extends OpenApiServices {
            public function __construct(
                PostmanCollectionGenerator $postmanGenerator,
                InsomniaWorkspaceGenerator $insomniaGenerator
            ) {
                parent::__construct();
                $this->postmanGenerator = $postmanGenerator;
                $this->insomniaGenerator = $insomniaGenerator;
            }

            protected function inspectRoutes(): void
            {
            }
        };

        $this->assertSame(
            ['postman' => true],
            $service->generate(false, ['movile'], null, 'postman')
        );
        $this->assertSame(
            ['insomnia' => true],
            $service->generate(false, ['movile'], null, 'insomnia')
        );
    }

    public function test_auth_login_template_is_reflected_in_openapi_postman_and_insomnia(): void
    {
        $this->app['config']->set('openapi.cache.enabled', false);

        // Use an enabled API prefix (admin) so OpenApiServices::isApiRoute()
        // does not filter the route out before the assertions run.
        Route::post('/admin/auth/login', static function () {
            return response()->json(['ok' => true]);
        })->name('auth.login');

        $service = new OpenApiServices();

        $openapi = $service->generate(false, null, null, 'openapi');
        $operation = $openapi['paths']['/admin/auth/login']['post'] ?? null;

        $this->assertNotNull($operation);
        $this->assertArrayHasKey('requestBody', $operation);

        $properties = $operation['requestBody']['content']['application/json']['schema']['properties'] ?? [];
        $this->assertArrayHasKey('email', $properties);
        $this->assertArrayHasKey('password', $properties);
        $this->assertArrayHasKey('remember', $properties);

        $postman = $service->generate(false, null, null, 'postman');
        $postmanPayload = json_encode($postman, JSON_THROW_ON_ERROR);
        // The Postman body is pretty-printed JSON embedded as a string, so the
        // surrounding json_encode escapes its quotes (\"email\": \"\").
        $this->assertStringContainsString('\"email\": \"\"', $postmanPayload);
        $this->assertStringContainsString('\"password\": \"\"', $postmanPayload);
        $this->assertStringContainsString('\"remember\": false', $postmanPayload);

        $insomnia = $service->generate(false, null, null, 'insomnia');
        $insomniaPayload = json_encode($insomnia, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('\"email\":\"\"', $insomniaPayload);
        $this->assertStringContainsString('\"password\":\"\"', $insomniaPayload);
        $this->assertStringContainsString('\"remember\":false', $insomniaPayload);
    }

}
