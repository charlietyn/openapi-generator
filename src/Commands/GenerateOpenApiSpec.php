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
                            {--api-type=* : Filter by API type (api, site, movile)}
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
        $apiTypes = $this->option('api-type');
        $withPostman = $this->option('with-postman');
        $withInsomnia = $this->option('with-insomnia');
        $environment = $this->option('environment');

        // Validate format
        if (!in_array($format, ['json', 'yaml', 'yml'])) {
            $this->error('Invalid format. Supported formats: json, yaml');
            return 1;
        }

        // Validate environment
        $envGen = app(EnvironmentGenerator::class);
        if (!$envGen->isValidEnvironment($environment) && $environment !== 'artisan') {
            $this->warn("Unknown environment '{$environment}', using 'artisan' as default");
            $environment = 'artisan';
        }

        try {
            // Apply API type filter
            if (!empty($apiTypes)) {
                $this->generator->setApiTypeFilter($apiTypes);
                $this->info('🔍 Filtering API types: ' . implode(', ', $apiTypes));
            } else {
                $this->info('📦 Generating all API types');
            }

            // Generate OpenAPI specification
            $this->info('📋 Inspecting routes...');
            $spec = $this->generator->generate($useCache);

            $routeCount = count($spec['paths'] ?? []);
            $this->info("✅ Found {$routeCount} unique paths");

            // Determine output path for OpenAPI
            if (!$output) {
                $extension = $format === 'json' ? 'json' : 'yaml';
                $output = storage_path("app/openapi.{$extension}");
            }

            // Convert and save OpenAPI
            $this->info('💾 Writing OpenAPI specification...');

            if ($format === 'json') {
                $content = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            } else {
                $content = Yaml::dump($spec, 10, 2);
            }

            File::put($output, $content);

            $this->info("✅ OpenAPI specification generated!");
            $this->line("📄 File: {$output}");
            $this->line("📦 Format: {$format}");
            $this->line("📢 Paths: {$routeCount}");

            // Generate Postman collection
            if ($withPostman) {
                $this->newLine();
                $this->info('📮 Generating Postman collection...');

                $postmanGen = app(PostmanCollectionGenerator::class);
                $collection = $postmanGen->generate($spec, $environment, $apiTypes);

                $postmanPath = storage_path('app/postman-collection.json');
                File::put($postmanPath, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                $this->info("✅ Postman collection generated!");
                $this->line("📄 File: {$postmanPath}");

                // Generate environments
                $this->info('📋 Generating Postman environments...');

                foreach (['artisan', 'local', 'production'] as $env) {
                    $envData = $envGen->generatePostman($env);
                    $envPath = storage_path("app/postman-env-{$env}.json");
                    File::put($envPath, json_encode($envData, JSON_PRETTY_PRINT));
                    $this->line("  ├─ {$env}: {$envPath}");
                }
            }

            // Generate Insomnia workspace
            if ($withInsomnia) {
                $this->newLine();
                $this->info('🛏️  Generating Insomnia workspace...');

                $insomniaGen = app(InsomniaWorkspaceGenerator::class);
                $workspace = $insomniaGen->generate($spec, $environment, $apiTypes);

                $insomniaPath = storage_path('app/insomnia-workspace.json');
                File::put($insomniaPath, json_encode($workspace, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                $this->info("✅ Insomnia workspace generated!");
                $this->line("📄 File: {$insomniaPath}");
                $this->line("  ├─ Includes 3 environments (base + artisan + local + production)");
                $this->line("  ├─ Minimal API Spec tab");
                $this->line("  └─ Automated tests included");
            }

            // Show import instructions
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
                $this->line('    1. Import postman-collection.json');
                $this->line('    2. Import each postman-env-*.json file');
                $this->line('    3. Select environment from dropdown');
            }

            if ($withInsomnia) {
                $this->line('');
                $this->line('  Insomnia Workspace:');
                $this->line('    1. Import insomnia-workspace.json');
                $this->line('    2. Environments are already included');
                $this->line('    3. Check Spec, Collection, and Test tabs');
            }

            $this->newLine();
            $this->info('✨ Generation complete!');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to generate specification');
            $this->error($e->getMessage());

            if ($this->output->isVerbose()) {
                $this->error($e->getTraceAsString());
            }

            return 1;
        }
    }
}