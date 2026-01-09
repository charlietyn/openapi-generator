<?php

namespace Ronu\OpenApiGenerator;

use Illuminate\Support\ServiceProvider;
use Ronu\OpenApiGenerator\Services\OpenApiServices;


class OpenApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register the OpenAPI Generator as a singleton
        $this->app->singleton(OpenApiServices::class, function ($app) {
            return new OpenApiServices();
        });

        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/openapi.php',
            'openapi'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/openapi.php' => config_path('openapi.php'),
            ], 'openapi-config');

            // Register commands
            $this->commands([
                \App\Console\Commands\GenerateOpenApiSpec::class,
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
