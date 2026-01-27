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
 * @package Ronu\OpenApiGenerator\Services\Documentation
 * @version 3.1.0 - Fixed Package Paths
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
     * Package templates base path
     * This is the fallback when user hasn't published templates
     */
    protected string $packageTemplatesPath;

    /**
     * User templates base path (published)
     * This takes priority over package templates
     */
    protected string $userTemplatesPath;

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
     *
     * CRITICAL FIX: Properly handle package vs user template paths
     *
     * Resolution order:
     * 1. User published templates (resource_path('openapi/templates'))
     * 2. Package templates (vendor/ronu/openapi-generator/resources/templates)
     */
    public function __construct()
    {
        $this->renderer = new ValidJSONTemplateProcessor(
            config('openapi-templates.debug', false)
        );
        $this->extractor = new MetadataExtractor();

        $this->packageTemplatesPath = __DIR__ . '/../../../resources/templates';

        // User published templates (optional, takes priority)
        $this->userTemplatesPath = resource_path('openapi/templates');

        $this->cacheEnabled = config('openapi-templates.cache_enabled', true);

        Log::channel('openapi')->debug('TemplateDocumentationResolver initialized', [
            'package_templates' => $this->packageTemplatesPath,
            'package_exists' => file_exists($this->packageTemplatesPath),
            'user_templates' => $this->userTemplatesPath,
            'user_exists' => file_exists($this->userTemplatesPath),
        ]);
    }

    /**
     * Resolve documentation for operation using JSON templates
     *
     * @param string $entity Entity name
     * @param string $action Action name
     * @param string $module Module name
     * @param string|null $controller Controller class
     * @param mixed|null $route Route object
     * @return array Documentation
     */
    public function resolveForOperation(
        string  $entity,
        string  $action,
        string  $module,
        ?string $controller = null,
        mixed $route = null
    ): array
    {
        $cacheKey = "{$module}.{$entity}.{$action}";

        if ($this->cacheEnabled && isset($this->cache[$cacheKey])) {
            Log::channel('openapi')->debug('Using cached documentation', ['key' => $cacheKey]);
            return $this->cache[$cacheKey];
        }

        // Extract metadata
        $metadata = $this->extractor->extractForEntity($entity, $module, $controller,$action, $route);

        // Try custom template
        $customTemplate = $this->findCustomTemplate($entity, $action);
        if ($customTemplate) {
            Log::channel('openapi')->info('Using custom template', [
                'entity' => $entity,
                'action' => $action,
                'template' => basename($customTemplate),
            ]);
            $result = $this->renderTemplate($customTemplate, $metadata);
            return $this->cacheResult($cacheKey, $result);
        }

        // Try generic template
        $genericTemplate = $this->findGenericTemplate($action);
        if ($genericTemplate) {
            Log::channel('openapi')->info('Using generic template', [
                'action' => $action,
                'template' => basename($genericTemplate),
            ]);
            $result = $this->renderTemplate($genericTemplate, $metadata);
            return $this->cacheResult($cacheKey, $result);
        }

        // Fallback to generic documentation
        Log::channel('openapi')->warning('No template found, using fallback', [
            'entity' => $entity,
            'action' => $action,
        ]);
        $result = $this->getGenericFallback($entity, $action, $metadata);
        return $this->cacheResult($cacheKey, $result);
    }

    /**
     * Find custom JSON template
     *
     * Searches in priority order:
     * 1. User published: resource_path('openapi/templates/custom/{entity}.{action}.json')
     * 2. Package: vendor/.../resources/templates/custom/{entity}.{action}.json
     *
     * @param string $entity Entity name
     * @param string $action Action name
     * @return string|null Template path
     */
    protected function findCustomTemplate(string $entity, string $action): ?string
    {
        $templateName = "custom/{$entity}.{$action}.json";
        return $this->getTemplatePath($templateName);
    }

    /**
     * Find generic JSON template
     *
     * Searches in priority order:
     * 1. User published: resource_path('openapi/templates/generic/{action}.json')
     * 2. Package: vendor/.../resources/templates/generic/{action}.json
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
            'update_multiple' => 'bulk_update',
        ];

        $templateName = $mapping[$action] ?? $action;
        return $this->getTemplatePath("generic/{$templateName}.json");
    }

    /**
     * Get template file path with fallback
     *
     * CRITICAL METHOD: Handles template resolution with proper priority
     *
     * Priority order:
     * 1. User-published templates (allows customization)
     * 2. Package templates (always available)
     *
     * @param string $templateName Template name (e.g., "generic/list.json")
     * @return string|null Full path to template file
     */
    protected function getTemplatePath(string $templateName): ?string
    {
        // Priority 1: User-published templates (custom overrides)
        $userPath = $this->userTemplatesPath . '/' . $templateName;
        if (file_exists($userPath)) {
            Log::channel('openapi')->debug('Found user template', [
                'template' => $templateName,
                'path' => $userPath,
            ]);
            return $userPath;
        }

        // Priority 2: Package templates (fallback, always available)
        $packagePath = $this->packageTemplatesPath . '/' . $templateName;
        if (file_exists($packagePath)) {
            Log::channel('openapi')->debug('Found package template', [
                'template' => $templateName,
                'path' => $packagePath,
            ]);
            return $packagePath;
        }

        // Not found anywhere
        Log::channel('openapi')->warning('Template not found', [
            'template' => $templateName,
            'searched_user' => $userPath,
            'searched_package' => $packagePath,
        ]);
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
}