<?php

namespace Ronu\OpenApiGenerator\Services\Documentation;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Template Documentation Resolver - JSON Version
 *
 * Uses JSON templates instead of YAML for better portability.
 *
 * Benefits of JSON over YAML:
 * ✅ Universal parsing (all languages)
 * ✅ No escaping issues
 * ✅ Consistent behavior
 * ✅ Portable to any project
 * ✅ Simple debugging
 *
 * Resolution precedence:
 * 1. Custom JSON template (custom/{entity}.{action}.json)
 * 2. Generic JSON template (generic/{action}.json)
 * 3. Fallback
 *
 * @package App\Services\Documentation
 * @version 3.0.0 - JSON Templates
 */
class TemplateDocumentationResolver
{
    /**
     * JSON Template Renderer (Mustache)
     */
    protected ValidJSONTemplateProcessor $renderer;

    /**
     * Metadata Extractor
     */
    protected MetadataExtractor $extractor;

    /**
     * Templates base path
     */
    protected string $templatesPath;

    /**
     * Enable caching
     */
    protected bool $cacheEnabled;

    /**
     * Cache storage
     */
    protected array $cache = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->renderer = new ValidJSONTemplateProcessor(
            config('openapi-templates.debug', false)
        );
        $this->extractor = new MetadataExtractor();
        $this->templatesPath = resource_path('openapi/templates');
        $this->cacheEnabled = config('openapi-templates.cache_enabled', true);

        $this->ensureTemplatesDirectory();
    }

    /**
     * Resolve documentation for operation using JSON templates
     *
     * @param string $entity Entity name
     * @param string $action Action name
     * @param string $module Module name
     * @param string|null $controller Controller class
     * @param mixed $route Route object
     * @return array Documentation
     */
    public function resolveForOperation(
        string  $entity,
        string  $action,
        string  $module,
        ?string $controller = null,
                $route = null
    ): array
    {
        $cacheKey = "{$module}.{$entity}.{$action}";

        // Check cache
        if ($this->cacheEnabled && isset($this->cache[$cacheKey])) {
            Log::channel('openapi')->debug('JSON Template cache hit', ['key' => $cacheKey]);
            return $this->cache[$cacheKey];
        }

        Log::channel('openapi')->info('Resolving documentation (JSON templates)', [
            'entity' => $entity,
            'action' => $action,
            'module' => $module,
        ]);

        // Extract metadata
        $metadata = $this->extractor->extractForEntity(
            $entity,
            $module,
            $controller,
            $action,
            $route
        );

        // Try custom JSON template first
        $customTemplate = $this->findCustomTemplate($entity, $action);
        if ($customTemplate) {
            Log::channel('openapi')->info('Using custom JSON template', [
                'template' => basename($customTemplate),
                'entity' => $entity,
                'action' => $action,
            ]);

            $result = $this->renderTemplate($customTemplate, $metadata);
            return $this->cacheResult($cacheKey, $result);
        }

        // Try generic JSON template
        $genericTemplate = $this->findGenericTemplate($action);
        if ($genericTemplate) {
            Log::channel('openapi')->info('Using generic JSON template', [
                'template' => basename($genericTemplate),
                'action' => $action,
            ]);

            $result = $this->renderTemplate($genericTemplate, $metadata);
            return $this->cacheResult($cacheKey, $result);
        }

        // Fallback
        Log::channel('openapi')->warning('No JSON template found, using fallback', [
            'entity' => $entity,
            'action' => $action,
        ]);

        $result = $this->getGenericFallback($entity, $action, $metadata);
        return $this->cacheResult($cacheKey, $result);
    }

    /**
     * Find custom template for specific entity.action
     *
     * Looks for: custom/{entity}.{action}.json
     * @param string $entity Entity name
     * @param string $action Action name
     * @return string|null Template path
     */
    protected function findCustomTemplate(string $entity, string $action): ?string
    {
        $action = Str::kebab($action);
        $templateName = "{$entity}.{$action}.json";
        $path = $this->templatesPath . "/custom/{$templateName}";

        if (File::exists($path)) {
            return $path;
        }

        return null;
    }

    /**
     * Find generic JSON template
     *
     * @param string $action Action name
     * @return string|null Template path
     */
    protected function findGenericTemplate(string $action): ?string
    {
        // Action aliases
        $mapping = [
            'index' => 'list',
            'store' => 'create',
            'edit' => 'update',
            'destroy' => 'delete',
        ];

        $templateName = $mapping[$action] ?? $action;
        $path = $this->templatesPath . "/generic/{$templateName}.json";

        if (File::exists($path)) {
            return $path;
        }

        return null;
    }

    /**
     * Render JSON template with metadata
     *
     * @param string $templatePath Template file path
     * @param array $metadata Metadata
     * @return array Documentation
     */
    protected function renderTemplate(string $templatePath, array $metadata): array
    {
        try {
            $parsed = $this->renderer->process($templatePath, $metadata);

            Log::channel('openapi')->debug('Template processed successfully', [
                'template' => basename($templatePath),
                'entity' => $metadata['entity'] ?? 'unknown',
                'action' => $metadata['action'] ?? 'unknown',
                'has_table_name' => !empty($metadata['table_name']),
                'has_request_example' => !empty($metadata['request_example']),
            ]);

            return [
                'summary' => $parsed['summary'] ?? '',
                'description' => $parsed['description'] ?? '',
                'full_spec' => $parsed,
                'request_example' => $metadata['request_example'] ?? [],
                'request_schema' => $metadata['request_schema'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::channel('openapi')->error('Template rendering failed', [
                'template' => basename($templatePath),
                'entity' => $metadata['entity'] ?? 'unknown',
                'action' => $metadata['action'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->getGenericFallback(
                $metadata['entity'],
                $metadata['action'],
                $metadata
            );
        }
    }

    /**
     * Get generic fallback documentation
     *
     * @param string $entity Entity name
     * @param string $action Action name
     * @param array $metadata Metadata
     * @return array Documentation
     */
    protected function getGenericFallback(string $entity, string $action, array $metadata): array
    {
        $entityTitle = Str::title(str_replace(['-', '_'], ' ', $entity));

        $summaryMap = [
            'list' => "List {$entityTitle}",
            'show' => "Get {$entityTitle}",
            'create' => "Create {$entityTitle}",
            'update' => "Update {$entityTitle}",
            'delete' => "Delete {$entityTitle}",
        ];

        return [
            'summary' => $summaryMap[$action] ?? ucfirst($action) . " {$entityTitle}",
            'description' => "Perform {$action} operation on {$entityTitle}.",
            'full_spec' => [
                'summary' => $summaryMap[$action] ?? ucfirst($action) . " {$entityTitle}",
                'description' => "Perform {$action} operation on {$entityTitle}.",
            ],
            'request_example' => $metadata['request_example'] ?? [],
            'request_schema' => $metadata['request_schema'] ?? [],
        ];
    }

    /**
     * Cache result
     *
     * @param string $key Cache key
     * @param array $result Result
     * @return array Result
     */
    protected function cacheResult(string $key, array $result): array
    {
        if ($this->cacheEnabled) {
            $this->cache[$key] = $result;
        }

        return $result;
    }

    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        $this->cache = [];
        $this->extractor->clearCache();
    }

    /**
     * Ensure templates directory exists
     */
    protected function ensureTemplatesDirectory(): void
    {
        $directories = [
            $this->templatesPath,
            $this->templatesPath . '/generic',
            $this->templatesPath . '/custom',
        ];

        foreach ($directories as $dir) {
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
                Log::channel('openapi')->info('Created templates directory', ['path' => $dir]);
            }
        }
    }
}