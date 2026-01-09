<?php

namespace Ronu\OpenApiGenerator\Services;


use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Ronu\OpenApiGenerator\Helpers\PlaceholderHelper;

/**
 * Insomnia Workspace Generator - v4.1 FIXED
 *
 * ✅ CRITICAL FIX: Base Environment now includes tracking_variables
 *
 * @package App\Services
 * @version 4.1.0
 */
class InsomniaWorkspaceGenerator
{
    protected string $workspaceId;
    protected array $spec;
    protected string $environment;
    protected array $apiTypes;
    protected TestTemplateResolver $testResolver;
    protected EnvironmentGenerator $envGenerator;
    protected array $requestIdMap = [];
    protected ?string $loginRequestId = null;
    protected ?string $baseEnvironmentId = null;

    public function __construct()
    {
        $this->workspaceId = 'wrk_' . $this->generateId();
        $this->testResolver = app(TestTemplateResolver::class);
        $this->envGenerator = app(EnvironmentGenerator::class);

        Log::channel('openapi')->info('InsomniaWorkspaceGenerator v4.1 initialized', [
            'workspace_id' => $this->workspaceId,
        ]);
    }

    /**
     * Generate Insomnia Workspace v4
     *
     * @param array $openApiSpec OpenAPI specification
     * @param string $environment Default environment name
     * @param array $apiTypes API types to include (empty = all)
     * @return array Complete Insomnia Workspace v4
     */
    public function generate(
        array $openApiSpec,
        string $environment = 'artisan',
        array $apiTypes = []
    ): array {
        $this->spec = $openApiSpec;
        $this->environment = $environment;
        $this->apiTypes = $apiTypes;

        Log::channel('openapi')->info('Generating Insomnia Workspace v4', [
            'environment' => $environment,
            'api_types' => $apiTypes,
            'paths_count' => count($openApiSpec['paths'] ?? []),
        ]);

        $timestamp = $this->getTimestamp();

        return [
            '_type' => 'export',
            '__export_format' => 4,
            '__export_date' => $this->getExportDate(),
            '__export_source' => 'ASGENS',
            'resources' => array_merge(
            // 1. Workspace
                [$this->buildWorkspace($timestamp)],

                // 2. Base Environment ← FIXED
                [$this->buildBaseEnvironment($timestamp)],

                // 3. API Spec
                [$this->buildApiSpec($timestamp)],

                // 4. Cookie Jar
                [$this->buildCookieJar($timestamp)],

                // 5. Folders and Requests
                $this->buildResourceGroups($timestamp),

                // 6. Sub-environments (artisan, local, production)
                $this->buildSubEnvironments($timestamp)
            ),
        ];
    }

    /**
     * Build workspace resource
     */
    protected function buildWorkspace(int $timestamp): array
    {
        $info = $this->spec['info'] ?? [];
        $apiTypesSuffix = !empty($this->apiTypes)
            ? ' (' . implode(', ', $this->apiTypes) . ')'
            : '';

        return [
            '_id' => $this->workspaceId,
            'parentId' => null,
            'modified' => $timestamp,
            'created' => $timestamp,
            'name' => ($info['title'] ?? 'Laravel API') . $apiTypesSuffix,
            'description' => $info['description'] ?? '',
            'scope' => 'design',
            '_type' => 'workspace',
        ];
    }

    /**
     * ✅ CRITICAL FIX: Build base environment resource WITH tracking_variables
     *
     * BEFORE: Only included base variables
     * AFTER: Merges variables + tracking_variables
     */
    protected function buildBaseEnvironment(int $timestamp): array
    {
        $baseConfig = config('openapi.environments.base', []);

        // ✅ FIX: Merge variables + tracking_variables
        $variables = array_merge(
            $baseConfig['variables'] ?? [],
            $baseConfig['tracking_variables'] ?? []
        );

        $this->baseEnvironmentId = 'env_' . $this->generateId();

        Log::channel('openapi')->info('✅ Base Environment built with tracking variables', [
            'total_variables' => count($variables),
            'base_variables' => count($baseConfig['variables'] ?? []),
            'tracking_variables' => count($baseConfig['tracking_variables'] ?? []),
        ]);

        return [
            '_id' => $this->baseEnvironmentId,
            'parentId' => $this->workspaceId,
            'modified' => $timestamp,
            'created' => $timestamp,
            'name' => 'Base Environment',
            'data' => $variables,  // ✅ Now includes tracking_variables
            'dataPropertyOrder' => $this->buildDataPropertyOrder($variables),
            'color' => null,
            'isPrivate' => false,
            'metaSortKey' => $timestamp,
            '_type' => 'environment',
        ];
    }

    /**
     * Build API spec resource
     */
    protected function buildApiSpec(int $timestamp): array
    {
        $info = $this->spec['info'] ?? [];
        $fileName = ($info['title'] ?? 'Laravel API') . 'Document';

        // Convert OpenAPI spec to YAML
        $yamlContent = $this->convertOpenApiToYaml($this->spec);

        return [
            '_id' => 'spc_' . $this->generateId(),
            'parentId' => $this->workspaceId,
            'modified' => $timestamp,
            'created' => $timestamp,
            'FileName' => $fileName,
            'contents' => $yamlContent,
            'contentType' => 'yaml',
            '_type' => 'api_spec',
        ];
    }

    /**
     * Build cookie jar resource
     */
    protected function buildCookieJar(int $timestamp): array
    {
        $info = $this->spec['info'] ?? [];
        $name = ($info['title'] ?? 'Laravel API') . 'Document';

        return [
            '_id' => 'jar_' . $this->generateId(),
            'parentId' => $this->workspaceId,
            'modified' => $timestamp + 1,
            'created' => $timestamp + 1,
            'name' => $name,
            'cookies' => [],
            '_type' => 'cookie_jar',
        ];
    }

    /**
     * Build resource groups (folders + requests)
     */
    protected function buildResourceGroups(int $timestamp): array
    {
        $resources = [];
        $paths = $this->spec['paths'] ?? [];

        // Group paths by API Type → Module → Entity
        $grouped = $this->groupPaths($paths);

        Log::channel('openapi')->debug('Building resource groups', [
            'api_types_count' => count($grouped),
        ]);

        $sortKey = $timestamp;

        // Build hierarchical folder structure
        foreach ($grouped as $apiType => $modules) {
            $apiResources = $this->buildApiTypeFolder($apiType, $modules, $sortKey);
            $resources = array_merge($resources, $apiResources);
            $sortKey += 10;
        }

        return $resources;
    }

    /**
     * Build API Type folder and its children
     */
    protected function buildApiTypeFolder(string $apiType, array $modules, int $baseSortKey): array
    {
        $resources = [];
        $apiConfig = config("openapi.api_types.{$apiType}", []);

        $baseName = $apiConfig['folder_name'] ?? ucfirst($apiType);
        $folderName = "{$baseName}({$apiType})";

        $timestamp = $this->getTimestamp();

        // API Type folder
        $apiFolderId = 'fld_' . $this->generateId();
        $resources[] = [
            '_id' => $apiFolderId,
            'parentId' => $this->workspaceId,
            'modified' => $timestamp,
            'created' => $timestamp,
            'name' => $folderName,
            'metaSortKey' => $baseSortKey,
            'description' => $apiConfig['description'] ?? '',
            'environment' => [],
            'environmentPropertyOrder' => null,
            '_type' => 'request_group',
        ];

        // Module folders
        $moduleSortKey = $baseSortKey;
        foreach ($modules as $module => $entities) {
            $moduleResources = $this->buildModuleFolder($module, $entities, $apiFolderId, $moduleSortKey);
            $resources = array_merge($resources, $moduleResources);
            $moduleSortKey += 10;
        }

        return $resources;
    }

    /**
     * Build module folder and its children
     */
    protected function buildModuleFolder(string $module, array $entities, string $parentId, int $baseSortKey): array
    {
        $resources = [];
        $timestamp = $this->getTimestamp();

        // Module folder
        $moduleFolderId = 'fld_' . $this->generateId();
        $resources[] = [
            '_id' => $moduleFolderId,
            'parentId' => $parentId,
            'modified' => $timestamp,
            'created' => $timestamp,
            'name' => ucfirst($module),
            'metaSortKey' => $baseSortKey,
            'description' => '',
            'environment' => [],
            'environmentPropertyOrder' => null,
            '_type' => 'request_group',
        ];

        // Entity folders
        $entitySortKey = $baseSortKey;
        foreach ($entities as $entity => $requests) {
            $entityResources = $this->buildEntityFolder($entity, $requests, $moduleFolderId, $entitySortKey);
            $resources = array_merge($resources, $entityResources);
            $entitySortKey += 10;
        }

        return $resources;
    }

    /**
     * Build entity folder and its requests
     */
    protected function buildEntityFolder(string $entity, array $requests, string $parentId, int $baseSortKey): array
    {
        $resources = [];
        $timestamp = $this->getTimestamp();

        // Entity folder
        $entityFolderId = 'fld_' . $this->generateId();
        $resources[] = [
            '_id' => $entityFolderId,
            'parentId' => $parentId,
            'modified' => $timestamp,
            'created' => $timestamp,
            'name' => ucfirst($entity),
            'metaSortKey' => $baseSortKey,
            'description' => '',
            'environment' => [],
            'environmentPropertyOrder' => null,
            '_type' => 'request_group',
        ];

        // Requests
        $requestSortKey = $baseSortKey;
        foreach ($requests as $request) {
            $requestResource = $this->buildRequest(
                $request['path'],
                $request['method'],
                $request['operation'],
                $entityFolderId,
                $requestSortKey
            );
            $resources[] = $requestResource;
            $requestSortKey += 10;
        }

        return $resources;
    }

    /**
     * Build individual request resource
     */
    protected function buildRequest(
        string $path,
        string $method,
        array $operation,
        string $parentId,
        int $sortKey
    ): array {
        $requestId = 'req_' . $this->generateId();
        $name = $operation['summary'] ?? $this->generateRequestName($path, $method);
        $url = $this->buildInsomniaUrl($path, $operation);
        $timestamp = $this->getTimestamp();

        // Store request ID for potential linking
        $this->requestIdMap[$path][$method] = $requestId;

        // Detect if login request
        if ($this->isLoginRequest($path, $method, $operation)) {
            $this->loginRequestId = $requestId;
            Log::channel('openapi')->info('Login request detected', [
                'id' => $requestId,
                'path' => $path,
                'method' => $method,
            ]);
        }

        // Load tests for this request
        $action = $operation['x-action-type'] ?? 'unknown';
        $entity = $operation['x-entity'] ?? 'resource';
        $tests = $this->loadTestsForRequest($action, $entity);

        $request = [
            '_id' => $requestId,
            'parentId' => $parentId,
            'modified' => $timestamp,
            'created' => $timestamp,
            'url' => $url,
            'name' => $name,
            'description' => $operation['description'] ?? '',
            'method' => strtoupper($method),
            'body' => $this->buildInsomniaBody($operation, $method),
            'parameters' => [],
            'headers' => $this->buildInsomniaHeaders($operation, $method),
            'authentication' => $this->buildAuthentication($operation),
            'metaSortKey' => $sortKey,
            'isPrivate' => false,
            'settingStoreCookies' => true,
            'settingSendCookies' => true,
            'settingDisableRenderRequestBody' => false,
            'settingEncodeUrl' => true,
            'settingRebuildPath' => true,
            'settingFollowRedirects' => 'global',
            '_type' => 'request',
        ];

        if ($tests !== null) {
            $request['afterResponseScript'] = $tests;
        }

        return $request;
    }

    protected function isLoginRequest(string $path, string $method, array $operation): bool
    {
        $summary = strtolower($operation['summary'] ?? '');
        $lowerPath = strtolower($path);
        $entity = strtolower($operation['x-entity'] ?? '');

        return (
            strtoupper($method) === 'POST' &&
            (
                str_contains($lowerPath, 'login') ||
                str_contains($summary, 'login') ||
                str_contains($summary, 'authentication') ||
                ($entity === 'auth' && str_contains($summary, 'user'))
            )
        );
    }

    protected function generateTokenTemplate(): string
    {
        if (!$this->loginRequestId) {
            Log::channel('openapi')->warning('No login request ID found, using empty token');
            return '';
        }

        return sprintf(
            "{%% response 'body', '%s', 'b64::JC50b2tlbg==::46b', 'never', 60 %%}",
            $this->loginRequestId
        );
    }

    protected function buildEnvironmentData(string $envName, string $tokenTemplate): array
    {
        $title = PlaceholderHelper::getProjectName();

        switch ($envName) {
            case 'artisan':
                return [
                    'base_url' => 'http://127.0.0.1:8000',
                    'token' => $tokenTemplate,
                    'api_key' => $this->envGenerator->generateApiKey(),
                ];

            case 'local':
                return [
                    'base_url' => PlaceholderHelper::replace('http://localhost/' . $title),
                    'token' => $tokenTemplate,
                    'api_key' => '',
                ];

            case 'production':
                return [
                    'base_url' => PlaceholderHelper::replace('http://' . $title . '.com'),
                    'token' => $tokenTemplate,
                    'api_key' => '',
                ];

            default:
                return [];
        }
    }

    protected function loadTestsForRequest(string $action, string $entity): ?string
    {
        $tests = $this->testResolver->generateInsomniaTest($action, $entity);
        if (empty($tests)) {
            return null;
        }
        return implode("\n", $tests);
    }

    /**
     * Build sub-environments (artisan, local, production)
     */
    protected function buildSubEnvironments(int $baseTimestamp): array
    {
        $environments = [];

        $tokenTemplate = $this->generateTokenTemplate();

        if (!$this->loginRequestId) {
            Log::channel('openapi')->warning('No login request found, token extraction will not work', [
                'total_requests' => count($this->requestIdMap),
            ]);
        }

        $subEnvNames = ['artisan', 'local', 'production'];
        $sortKey = $baseTimestamp;

        foreach ($subEnvNames as $envName) {
            $envConfig = config("openapi.environments.{$envName}", []);

            if (empty($envConfig)) {
                continue;
            }

            $data = $this->buildEnvironmentData($envName, $tokenTemplate);

            $timestamp = $this->getTimestamp();
            $sortKey += 1;

            $environments[] = [
                '_id' => 'env_' . $this->generateId(),
                'parentId' => $this->baseEnvironmentId,
                'modified' => $sortKey,
                'created' => $sortKey,
                'name' => ucfirst($envName) . ' Environment',
                'data' => $data,
                'dataPropertyOrder' => $this->buildDataPropertyOrder($data),
                'color' => null,
                'isPrivate' => false,
                'metaSortKey' => $sortKey,
                '_type' => 'environment',
            ];
        }

        return $environments;
    }

    /**
     * Build authentication object
     */
    protected function buildAuthentication(array $operation): array
    {
        $security = $operation['security'] ?? [];

        if (empty($security)) {
            return [];
        }

        foreach ($security as $securityScheme) {
            if (isset($securityScheme['BearerAuth'])) {
                return [
                    'type' => 'bearer',
                    'token' => '{{ _.token }}',
                ];
            }
        }

        return [];
    }

    /**
     * Build Insomnia URL with variables
     */
    protected function buildInsomniaUrl(string $path, array $operation): string
    {
        $parts = explode('/', trim($path, '/'));
        $urlParts = [];

        foreach ($parts as $part) {
            if (preg_match('/\{([^}]+)\}/', $part, $matches)) {
                $paramName = $matches[1];

                $variableName = $this->getVariableNameForParameter(
                    $paramName,
                    $operation['parameters'] ?? []
                );

                $urlParts[] = "{{ _.{$variableName} }}";
            } else {
                $urlParts[] = $part;
            }
        }

        return "{{ _.base_url }}/" . implode('/', $urlParts);
    }

    /**
     * Get variable name for parameter (GLOBAL vs LOCAL logic)
     */
    protected function getVariableNameForParameter(string $paramName, array $parameters): string
    {
        foreach ($parameters as $param) {
            if ($param['name'] === $paramName && isset($param['x-variable-name'])) {
                return $param['x-variable-name'];
            }
        }

        return $paramName;
    }

    /**
     * Build request body
     */
    protected function buildInsomniaBody(array $operation, string $method): array|null
    {
        if (!in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            return ['mimeType' => 'application/json', 'text' => null];
        }

        $requestBody = $operation['requestBody'] ?? null;
        if (!$requestBody) {
            return ['mimeType' => 'application/json', 'text' => '{}'];
        }

        $schema = $requestBody['content']['application/json']['schema'] ?? null;
        if (!$schema) {
            return ['mimeType' => 'application/json', 'text' => '{}'];
        }

        $example = $this->generateEmptyExample($schema);

        return [
            'mimeType' => 'application/json',
            'text' => json_encode($example, JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * Generate empty example from JSON schema
     */
    protected function generateEmptyExample(array $schema): array
    {
        if ($schema['type'] === 'object' && isset($schema['properties'])) {
            $example = [];

            foreach ($schema['properties'] as $field => $fieldSchema) {
                $example[$field] = $this->getEmptyValue($fieldSchema);
            }

            return $example;
        }

        return [];
    }

    /**
     * Get empty value for schema type
     */
    protected function getEmptyValue(array $schema): string|int|float|bool|array
    {
        $type = $schema['type'] ?? 'string';

        return match ($type) {
            'string' => '',
            'integer' => 0,
            'number' => 0.0,
            'boolean' => false,
            'array' => [],
            default => '',
        };
    }

    /**
     * Build request headers
     */
    protected function buildInsomniaHeaders(array $operation, string $method): array
    {
        return [
            [
                'name' => 'X-API-Key',
                'value' => '{{ _.api_key }}',
            ],
            [
                'name' => 'Content-Type',
                'value' => 'application/json',
            ],
        ];
    }

    /**
     * Build dataPropertyOrder
     */
    protected function buildDataPropertyOrder(array $data): array
    {
        return [
            '&' => array_keys($data),
        ];
    }

    /**
     * Group paths by API Type → Module → Entity
     */
    protected function groupPaths(array $paths): array
    {
        $grouped = [];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                $module = $operation['x-module'] ?? 'general';
                $entity = $operation['x-entity'] ?? 'resource';
                $apiType = $this->getApiTypeFromPath($path);

                if (!empty($this->apiTypes) && !in_array($apiType, $this->apiTypes)) {
                    continue;
                }

                $grouped[$apiType][$module][$entity][] = [
                    'path' => $path,
                    'method' => $method,
                    'operation' => $operation,
                ];
            }
        }

        return $grouped;
    }

    /**
     * Get API type from path
     */
    protected function getApiTypeFromPath(string $path): string
    {
        $pathParts = explode('/', trim($path, '/'));
        $prefix = $pathParts[0] ?? 'api';

        $apiTypes = config('openapi.api_types', []);
        foreach ($apiTypes as $key => $config) {
            if ($config['prefix'] === $prefix) {
                return $key;
            }
        }

        return 'api';
    }

    /**
     * Convert OpenAPI spec to YAML string
     */
    protected function convertOpenApiToYaml(array $spec): string
    {
        $cleanSpec = $spec;
        $cleanSpec['paths'] = [];
        unset($cleanSpec['components']);
        unset($cleanSpec['tags']);

        if (class_exists('\Symfony\Component\Yaml\Yaml')) {
            return \Symfony\Component\Yaml\Yaml::dump($cleanSpec, 10, 2);
        }

        return '';
    }

    /**
     * Generate request name fallback
     */
    protected function generateRequestName(string $path, string $method): string
    {
        $parts = explode('/', trim($path, '/'));
        $resource = end($parts);
        return strtoupper($method) . ' ' . $resource;
    }

    /**
     * Generate unique ID (32 chars like Insomnia)
     */
    protected function generateId(): string
    {
        return Str::random(32);
    }

    /**
     * Get current timestamp in milliseconds
     */
    protected function getTimestamp(): int
    {
        return (int)(microtime(true) * 1000);
    }

    /**
     * Get export date in Insomnia format
     */
    protected function getExportDate(): string
    {
        return Carbon::now()->format('Y-m-d-\TH:i:s\Z');
    }

    /**
     * Get request ID map
     */
    public function getRequestIdMap(): array
    {
        return $this->requestIdMap;
    }
}