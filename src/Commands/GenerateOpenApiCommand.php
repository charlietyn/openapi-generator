<?php

namespace Ronu\OpenApiGenerator\Commands;

use Illuminate\Console\Command;
use Ronu\OpenApiGenerator\Services\OpenApiService;
use Ronu\OpenApiGenerator\Validators\SpecValidator;

/**
 * Generate OpenAPI Documentation Command
 */
class GenerateOpenApiCommand extends Command
{
    protected $signature = 'openapi:generate
        {--api-type=* : API types to generate (api, mobile, admin)}
        {--format=all : Output format (openapi, postman, insomnia, all)}
        {--output= : Custom output directory}
        {--validate : Validate generated specification}
        {--fail-on-errors : Fail if validation errors found}';
    
    protected $description = 'Generate OpenAPI documentation from routes, FormRequests, and Models';
    
    protected OpenApiService $service;
    protected ?SpecValidator $validator;
    
    public function __construct(OpenApiService $service, ?SpecValidator $validator = null)
    {
        parent::__construct();
        $this->service = $service;
        $this->validator = $validator;
    }
    
    public function handle(): int
    {
        $this->info('🚀 OpenAPI Generation Started');
        $this->newLine();
        
        $startTime = microtime(true);
        
        // Get options
        $apiTypes = $this->option('api-type') ?: array_keys(config('openapi-generator.api_types'));
        $format = $this->option('format');
        $outputPath = $this->option('output');
        $validate = $this->option('validate');
        
        // Display configuration
        $this->displayConfiguration($apiTypes, $format);
        
        try {
            // Generate documentation
            $this->info('📝 Generating documentation...');
            
            $generated = $this->service->generate([
                'api_types' => $apiTypes,
                'format' => $format,
                'output_path' => $outputPath,
            ]);
            
            $this->newLine();
            $this->displayResults($generated);
            
            // Validate if requested
            if ($validate && $this->validator) {
                $this->newLine();
                $this->validateSpecification($generated);
            }
            
            $duration = round(microtime(true) - $startTime, 2);
            
            $this->newLine();
            $this->info("✅ Generation complete! ({$duration}s)");
            
            return self::SUCCESS;
            
        } catch (\Throwable $e) {
            $this->error('❌ Generation failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }
    
    protected function displayConfiguration(array $apiTypes, string $format): void
    {
        $this->line('📋 Configuration:');
        $this->line('  API Types: ' . implode(', ', $apiTypes));
        $this->line('  Format: ' . $format);
        $this->newLine();
    }
    
    protected function displayResults(array $generated): void
    {
        $this->info('💾 Files created:');
        
        foreach ($generated['files'] as $file) {
            $this->line("  • {$file}");
        }
        
        if (isset($generated['stats'])) {
            $this->newLine();
            $this->info('📊 Statistics:');
            $this->line("  Routes analyzed: {$generated['stats']['routes_analyzed']}");
            $this->line("  Endpoints generated: {$generated['stats']['endpoints_generated']}");
            $this->line("  Extraction success rate: {$generated['stats']['extraction_rate']}%");
        }
    }
    
    protected function validateSpecification(array $generated): void
    {
        if (!isset($generated['files']['openapi'])) {
            return;
        }
        
        $this->info('🔍 Validating specification...');
        
        $result = $this->validator->validate($generated['files']['openapi']);
        
        if (!$result['valid']) {
            $this->error('❌ Validation failed:');
            foreach ($result['errors'] as $error) {
                $this->line("  • {$error}");
            }
            
            if ($this->option('fail-on-errors')) {
                exit(1);
            }
        } else {
            $this->info('✅ Validation passed');
        }
        
        if (!empty($result['warnings'])) {
            $this->warn("⚠️  Warnings ({count(".$result['warnings'].")}):");
            foreach (array_slice($result['warnings'], 0, 5) as $warning) {
                $this->line("  • {$warning}");
            }
        }
        
        if (isset($result['metrics'])) {
            $this->newLine();
            $this->info('📊 Quality Metrics:');
            $this->line("  Summary coverage: {$result['metrics']['summary_coverage']}%");
            $this->line("  Description coverage: {$result['metrics']['description_coverage']}%");
            $this->line("  Quality score: {$result['metrics']['quality_score']}/100");
        }
    }
}
