<?php

namespace Ronu\OpenApiGenerator\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Ronu\OpenApiGenerator\Services\EnvironmentGenerator;
use Ronu\OpenApiGenerator\Services\InsomniaWorkspaceGenerator;
use Ronu\OpenApiGenerator\Services\OpenApiServices;
use Ronu\OpenApiGenerator\Services\PostmanCollectionGenerator;
use Symfony\Component\Yaml\Yaml;

/**
 * Generate OpenAPI Specification Command
 *
 * Generates OpenAPI specs, Postman collections, and Insomnia workspaces
 * from Laravel routes with support for filtering by API type.
 *
 * Usage Examples:
 * - php artisan openapi:generate --api-type=admin --with-insomnia
 *   → Generates OpenAPI + Insomnia ONLY for admin apiType
 *
 * - php artisan openapi:generate --api-type=admin --with-postman
 *   → Generates OpenAPI + Postman ONLY for admin apiType
 *
 * - php artisan openapi:generate --all
 *   → Generates OpenAPI + Postman + Insomnia for ALL apiTypes
 *
 * - php artisan openapi:generate --api-type=admin --api-type=mobile --with-postman
 *   → Generates OpenAPI + Postman for admin AND mobile apiTypes
 */
class GenerateOpenApiSpec extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openapi:generate
                            {--format=json : Output format (json or yaml)}
                            {--output= : Output file path}
                            {--no-cache : Disable cache}
                            {--api-type=* : Filter by API type (api, site, mobile, admin)}
                            {--all : Generate all formats for all channels (OpenAPI + Postman + Insomnia)}
                            {--with-postman : Generate Postman collection}
                            {--with-insomnia : Generate Insomnia workspace}
                            {--environment=artisan : Environment to use (artisan, local, production)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate OpenAPI specification from Laravel routes';

    protected OpenApiServices $generator;

    /**
     * Create a new command instance.
     *
     * @param OpenApiServices $generator
     */
    public function __construct(OpenApiServices $generator)
    {
        parent::__construct();
        $this->generator = $generator;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('🚀 Generating OpenAPI Specification...');
        $this->newLine();

        $format = $this->option('format');
        $output = $this->option('output');
        $useCache = !$this->option('no-cache');
        $apiTypes = $this->normalizeApiTypes($this->option('api-type'));
        $generateAll = (bool)$this->option('all');
        $withPostman = $this->option('with-postman');
        $withInsomnia = $this->option('with-insomnia');
        $environment = $this->option('environment');
        $outputPath = config('openapi.output_path', storage_path('app/public/openapi'));

        // Validate format
        if (!in_array($format, ['json', 'yaml', 'yml'])) {
            $this->error('Invalid format. Supported formats: json, yaml');
            return self::FAILURE;
        }

        // Validate environment
        $envGen = app(EnvironmentGenerator::class);
        if (!$envGen->isValidEnvironment($environment) && $environment !== 'artisan') {
            $this->warn("Unknown environment '{$environment}', using 'artisan' as default");
            $environment = 'artisan';
        }

        try {
            // Handle --all flag: generates everything for all apiTypes
            if ($generateAll) {
                return $this->handleGenerateAll($format, $output, $outputPath, $useCache, $environment);
            }

            // Validate apiTypes if specified
            $this->validateApiTypes($apiTypes);

            // Apply API type filter
            if (!empty($apiTypes)) {
                $this->generator->setApiTypeFilter($apiTypes);
                $this->info('🔍 Filtering API types: ' . implode(', ', $apiTypes));
            } else {
                $this->info('📦 Generating for all API types');
            }

            // Generate OpenAPI specification
            $this->info('📋 Inspecting routes...');
            $spec = $this->generator->generate($useCache, $apiTypes, $environment, 'openapi');

            $routeCount = count($spec['paths'] ?? []);
            $this->info("✅ Found {$routeCount} unique paths");

            if ($routeCount === 0) {
                $this->warn('⚠️  No routes found matching the specified filters');
                return self::SUCCESS;
            }

            // Determine output path for OpenAPI
            $openapiOutput = $output;
            if (!$openapiOutput) {
                $extension = $format === 'json' ? 'json' : 'yaml';
                $filename = $this->generator->generateFilename('openapi', $apiTypes, null);
                $filename = str_replace('.json', '.' . $extension, $filename);
                $openapiOutput = $outputPath . DIRECTORY_SEPARATOR . $filename;
            }

            // Write OpenAPI spec
            $this->writeOpenApiSpec($spec, $format, $openapiOutput);

            // Generate Postman collection if requested
            if ($withPostman) {
                $this->generatePostmanCollection($spec, $apiTypes, $environment, $outputPath);
            }

            // Generate Insomnia workspace if requested
            if ($withInsomnia) {
                $this->generateInsomniaWorkspace($spec, $apiTypes, $environment, $outputPath);
            }

            // Show import instructions
            $this->showImportInstructions($withPostman, $withInsomnia);

            $this->newLine();
            $this->info('✨ Generation complete!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to generate specification');
            $this->error($e->getMessage());

            if ($this->output->isVerbose()) {
                $this->error($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    /**
     * Handle --all flag: generates OpenAPI + Postman + Insomnia for ALL apiTypes
     */
    private function handleGenerateAll(
        string  $format,
        ?string $output,
        string  $outputPath,
        bool    $useCache,
        string  $environment
    ): int {
        $this->info('📦 Generating ALL artifacts for ALL API types');
        $this->newLine();

        // Generate spec for ALL apiTypes (no filter)
        $this->info('📋 Inspecting routes...');
        $spec = $this->generator->generate($useCache, null, $environment, 'openapi');

        $routeCount = count($spec['paths'] ?? []);
        $this->info("✅ Found {$routeCount} unique paths");

        if ($routeCount === 0) {
            $this->warn('⚠️  No routes found');
            return self::SUCCESS;
        }

        // Determine output path for OpenAPI
        $openapiOutput = $output;
        if (!$openapiOutput) {
            $extension = $format === 'json' ? 'json' : 'yaml';
            $openapiOutput = $outputPath . DIRECTORY_SEPARATOR . "openapi-all.{$extension}";
        }

        // Write OpenAPI spec
        $this->writeOpenApiSpec($spec, $format, $openapiOutput);

        // Generate Postman collection (all apiTypes)
        $this->generatePostmanCollection($spec, [], $environment, $outputPath);

        // Generate Insomnia workspace (all apiTypes)
        $this->generateInsomniaWorkspace($spec, [], $environment, $outputPath);

        // Show import instructions
        $this->showImportInstructions(true, true);

        $this->newLine();
        $this->info('✨ Generation complete! (--all mode)');

        return self::SUCCESS;
    }

    /**
     * Write OpenAPI specification to file
     */
    private function writeOpenApiSpec(array $spec, string $format, string $output): void
    {
        $this->info('💾 Writing OpenAPI specification...');

        $content = $format === 'json'
            ? json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            : Yaml::dump($spec, 10, 2);

        File::ensureDirectoryExists(dirname($output));
        File::put($output, $content);

        $routeCount = count($spec['paths'] ?? []);

        $this->info("✅ OpenAPI specification generated!");
        $this->line("📄 File: {$output}");
        $this->line("📦 Format: {$format}");
        $this->line("📢 Paths: {$routeCount}");
    }

    /**
     * Generate Postman collection
     */
    private function generatePostmanCollection(
        array  $spec,
        array  $apiTypes,
        string $environment,
        string $outputPath
    ): void {
        $this->newLine();
        $this->info('📮 Generating Postman collection...');

        $postmanGen = app(PostmanCollectionGenerator::class);
        $collection = $postmanGen->generate($spec, $environment, $apiTypes);
        $fileName = $this->generator->generateFilename('postman', $apiTypes ?: null, null);
        $postmanPath = $outputPath . DIRECTORY_SEPARATOR . $fileName;

        File::ensureDirectoryExists(dirname($postmanPath));
        File::put($postmanPath, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("✅ Postman collection generated!");
        $this->line("📄 File: {$postmanPath}");

        // Generate environments
        $this->info('📋 Generating Postman environments...');
        $envGen = app(EnvironmentGenerator::class);

        foreach (['artisan', 'local', 'production'] as $env) {
            $envData = $envGen->generatePostman($env);
            $envPath = $outputPath . DIRECTORY_SEPARATOR . "postman-env-{$env}.json";
            File::put($envPath, json_encode($envData, JSON_PRETTY_PRINT));
            $this->line("  ├─ {$env}: {$envPath}");
        }
    }

    /**
     * Generate Insomnia workspace
     */
    private function generateInsomniaWorkspace(
        array  $spec,
        array  $apiTypes,
        string $environment,
        string $outputPath
    ): void {
        $this->newLine();
        $this->info('🛏️  Generating Insomnia workspace...');

        $insomniaGen = app(InsomniaWorkspaceGenerator::class);
        $workspace = $insomniaGen->generate($spec, $environment, $apiTypes);
        $fileName = $this->generator->generateFilename('insomnia', $apiTypes ?: null, null);
        $insomniaPath = $outputPath . DIRECTORY_SEPARATOR . $fileName;

        File::ensureDirectoryExists(dirname($insomniaPath));
        File::put($insomniaPath, json_encode($workspace, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("✅ Insomnia workspace generated!");
        $this->line("📄 File: {$insomniaPath}");
        $this->line("  ├─ Includes 3 environments (base + artisan + local + production)");
        $this->line("  ├─ Minimal API Spec tab");
        $this->line("  └─ Automated tests included");
    }

    /**
     * Show import instructions
     */
    private function showImportInstructions(bool $withPostman, bool $withInsomnia): void
    {
        $this->newLine();
        $this->comment('📥 Import instructions:');
        $this->line('');
        $this->line('  OpenAPI Spec:');
        $this->line('    ├─ Swagger Editor: https://editor.swagger.io');
        $this->line('    ├─ Postman: File > Import > Upload Files');
        $this->line('    └─ Insomnia: Import/Export > Import Data > From File');

        if ($withPostman) {
            $this->line('');
            $this->line('  Postman Collection:');
            $this->line('    1. Import postman-<channel>.json or postman-all.json');
            $this->line('    2. Import each postman-env-*.json file');
            $this->line('    3. Select environment from dropdown');
        }

        if ($withInsomnia) {
            $this->line('');
            $this->line('  Insomnia Workspace:');
            $this->line('    1. Import insomnia-<channel>.json or insomnia-all.json');
            $this->line('    2. Environments are already included');
            $this->line('    3. Check Spec, Collection, and Test tabs');
        }
    }

    /**
     * Normalize API type aliases to the current keys.
     *
     * @param array $types
     * @return array
     */
    private function normalizeApiTypes(array $types): array
    {
        $normalized = [];
        $hasLegacyMobile = false;

        foreach ($types as $type) {
            if ($type === 'movile') {
                $hasLegacyMobile = true;
                $type = 'mobile';
            }
            $normalized[] = $type;
        }

        if ($hasLegacyMobile) {
            $this->warn("API type 'movile' is deprecated. Use 'mobile' instead.");
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Validate that API types exist and are enabled.
     */
    private function validateApiTypes(array $types): void
    {
        if (empty($types)) {
            return;
        }

        $available = array_keys($this->getEnabledApiTypes());
        $invalid = array_diff($types, $available);

        if (!empty($invalid)) {
            throw new \InvalidArgumentException(
                'Unknown or disabled API types: ' . implode(', ', $invalid) .
                '. Available types: ' . implode(', ', $available)
            );
        }
    }

    /**
     * Get enabled API types from configuration.
     */
    private function getEnabledApiTypes(): array
    {
        $apiTypes = config('openapi.api_types', []);

        return array_filter(
            $apiTypes,
            static fn(array $config): bool => ($config['enabled'] ?? true) === true
        );
    }
}
