<?php

namespace Ronu\OpenApiGenerator\Providers;

use Illuminate\Support\ServiceProvider;
use Ronu\OpenApiGenerator\Commands\GenerateOpenApiSpec;
use Ronu\OpenApiGenerator\Services\OpenApiServices;


class OpenApiGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register services
        $this->app->singleton(OpenApiServices::class, function ($app) {
            return new OpenApiServices();
        });

        // ✅ CORRECT: Use proper package config path
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/openapi.php',
            'openapi'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/openapi-docs.php',
            'openapi-docs'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/openapi-tests.php',
            'openapi-tests'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/openapi-templates.php',
            'openapi-templates'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'openapi');

        if (config('openapi.routes.enabled')) {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        }

        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/openapi.php' => config_path('openapi.php'),
                __DIR__ . '/../../config/openapi-docs.php' => config_path('openapi-docs.php'),
                __DIR__ . '/../../config/openapi-tests.php' => config_path('openapi-tests.php'),
                __DIR__ . '/../../config/openapi-templates.php' => config_path('openapi-templates.php'),
            ], 'openapi-config');

            $this->publishes([
                __DIR__ . '/../../resources/templates' => resource_path('openapi/templates'),
            ], 'openapi-templates');


            // Register commands
            $this->commands([
               GenerateOpenApiSpec::class,
            ]);
        }

        // Register cache clear event listener
        $this->registerCacheClearListener();
    }

    /**
     * Register event listener to clear cache when routes change
     *
     * @return void
     */
    protected function registerCacheClearListener(): void
    {
        // Clear OpenAPI cache when route cache is cleared
        if ($this->app->routesAreCached()) {
            return;
        }

        // You can add event listeners here for automatic cache invalidation
        // Example: when routes are updated, models change, etc.
    }
}
