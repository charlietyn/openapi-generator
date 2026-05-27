<?php

namespace Ronu\OpenApiGenerator\Tests\Feature;

use Ronu\OpenApiGenerator\Services\OpenApiServices;
use Ronu\OpenApiGenerator\Tests\TestCase;

class ModuleRootRouteExclusionTest extends TestCase
{
    public function test_module_root_route_is_detected_by_structure_without_directory(): void
    {
        $service = new class extends OpenApiServices {
            public function detect(string $uri): bool
            {
                return $this->isModuleRootRoute($uri);
            }
        };

        $this->assertTrue($service->detect('/admin/mod_clients'));
        $this->assertFalse($service->detect('/admin/mod_clients/client'));
    }

    public function test_strict_mode_excludes_only_module_root_routes_before_process(): void
    {
        $service = new class extends OpenApiServices {
            public function shouldExcludeUri(string $uri): bool
            {
                $route = new class($uri) {
                    public function __construct(private string $uri)
                    {
                    }

                    public function uri(): string
                    {
                        return ltrim($this->uri, '/');
                    }
                };

                return $this->shouldExcludeBeforeProcess($route) !== null;
            }
        };

        $this->app['config']->set('openapi.exclude_prefix_module_roots', true);
        $this->assertTrue($service->shouldExcludeUri('/admin/mod_clients'));
        $this->assertFalse($service->shouldExcludeUri('/admin/mod_clients/client'));

        $this->app['config']->set('openapi.exclude_prefix_module_roots', false);
        $this->assertFalse($service->shouldExcludeUri('/admin/mod_clients'));
    }
}
