<?php

namespace Ronu\OpenApiGenerator;

use Illuminate\Support\ServiceProvider;
use Ronu\OpenApiGenerator\Commands\GenerateOpenApiCommand;
use Ronu\OpenApiGenerator\Services\OpenApiService;
use Ronu\OpenApiGenerator\Services\MetadataOrchestrator;
use Ronu\OpenApiGenerator\Extractors\{
    FormRequestExtractor,
    ModelExtractor,
    ConfigExtractor
};
use Ronu\OpenApiGenerator\Generators\{
    SmartDescriptionGenerator,
    RealisticExampleGenerator
};
use Ronu\OpenApiGenerator\Validators\SpecValidator;
use Ronu\OpenApiGenerator\Services\Exporters\{
    OpenApiExporter,
    PostmanExporter,
    InsomniaExporter
};

/**
 * OpenAPI Generator Service Provider
 * 
 * Registers all package services, commands, and publishes configuration
 */
class OpenApiGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap package services
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/openapi-generator.php' => config_path('openapi-generator.php'),
        ], 'openapi-config');
        
        // Publish templates
        $this->publishes([
            __DIR__ . '/../resources/templates' => resource_path('openapi/templates'),
        ], 'openapi-templates');
        
        // Publish all
        $this->publishes([
            __DIR__ . '/../config/openapi-generator.php' => config_path('openapi-generator.php'),
            __DIR__ . '/../resources/templates' => resource_path('openapi/templates'),
        ], 'openapi');
        
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateOpenApiCommand::class,
            ]);
        }
    }
    
    /**
     * Register package services
     */
    public function register(): void
    {
        // Merge package config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/openapi-generator.php',
            'openapi-generator'
        );
        
        // Register core services
        $this->registerCoreServices();
        
        // Register extractors
        $this->registerExtractors();
        
        // Register generators
        $this->registerGenerators();
        
        // Register validators
        $this->registerValidators();
        
        // Register exporters
        $this->registerExporters();
        
        // Register metadata orchestrator
        $this->registerOrchestrator();
        
        // Register facade
        $this->app->singleton('openapi-generator', function ($app) {
            return $app->make(OpenApiService::class);
        });
    }
    
    /**
     * Register core services
     */
    protected function registerCoreServices(): void
    {
        $this->app->singleton(OpenApiService::class, function ($app) {
            return new OpenApiService(
                $app->make(MetadataOrchestrator::class),
                $app->make(SmartDescriptionGenerator::class),
                $app->make(RealisticExampleGenerator::class),
                $app->make(SpecValidator::class)
            );
        });
    }
    
    /**
     * Register metadata extractors
     */
    protected function registerExtractors(): void
    {
        // FormRequest Extractor
        $this->app->singleton(FormRequestExtractor::class, function ($app) {
            return new FormRequestExtractor(config('openapi-generator'));
        });
        
        // Model Extractor
        $this->app->singleton(ModelExtractor::class, function ($app) {
            return new ModelExtractor(config('openapi-generator'));
        });
        
        // Config Extractor (fallback)
        $this->app->singleton(ConfigExtractor::class, function ($app) {
            return new ConfigExtractor(config('openapi-generator'));
        });
        
        // Register custom extractors from config
        $customExtractors = config('openapi-generator.custom_extractors', []);
        foreach ($customExtractors as $extractorClass) {
            if (class_exists($extractorClass)) {
                $this->app->singleton($extractorClass);
            }
        }
    }
    
    /**
     * Register smart generators
     */
    protected function registerGenerators(): void
    {
        // Description Generator
        $descriptionGeneratorClass = config(
            'openapi-generator.generators.description',
            SmartDescriptionGenerator::class
        );
        $this->app->singleton(SmartDescriptionGenerator::class, $descriptionGeneratorClass);
        
        // Example Generator
        $exampleGeneratorClass = config(
            'openapi-generator.generators.example',
            RealisticExampleGenerator::class
        );
        $this->app->singleton(RealisticExampleGenerator::class, $exampleGeneratorClass);
    }
    
    /**
     * Register validators
     */
    protected function registerValidators(): void
    {
        $this->app->singleton(SpecValidator::class, function ($app) {
            return new SpecValidator();
        });
    }
    
    /**
     * Register exporters
     */
    protected function registerExporters(): void
    {
        $this->app->singleton(OpenApiExporter::class);
        $this->app->singleton(PostmanExporter::class);
        $this->app->singleton(InsomniaExporter::class);
    }
    
    /**
     * Register metadata orchestrator
     */
    protected function registerOrchestrator(): void
    {
        $this->app->singleton(MetadataOrchestrator::class, function ($app) {
            $orchestrator = new MetadataOrchestrator();
            
            // Register extractors in priority order (lowest to highest)
            // Priority 5 (lowest) - Config fallback
            $orchestrator->registerExtractor($app->make(ConfigExtractor::class));
            
            // Priority 4 - Model metadata
            $orchestrator->registerExtractor($app->make(ModelExtractor::class));
            
            // Priority 3 - FormRequest validation
            $orchestrator->registerExtractor($app->make(FormRequestExtractor::class));
            
            // Register custom extractors
            $customExtractors = config('openapi-generator.custom_extractors', []);
            foreach ($customExtractors as $extractorClass) {
                if (class_exists($extractorClass)) {
                    $orchestrator->registerExtractor($app->make($extractorClass));
                }
            }
            
            return $orchestrator;
        });
    }
    
    /**
     * Get package providers
     */
    public function provides(): array
    {
        return [
            'openapi-generator',
            OpenApiService::class,
            MetadataOrchestrator::class,
            FormRequestExtractor::class,
            ModelExtractor::class,
            ConfigExtractor::class,
            SmartDescriptionGenerator::class,
            RealisticExampleGenerator::class,
            SpecValidator::class,
            OpenApiExporter::class,
            PostmanExporter::class,
            InsomniaExporter::class,
        ];
    }
}
