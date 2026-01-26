<?php

namespace Ronu\OpenApiGenerator\Services;


use Illuminate\Support\Str;

/**
 * Postman Collection Generator
 *
 * Generates Postman Collection v2.1 from OpenAPI specification.
 * Creates hierarchical folder structure: API Type → Module → Entity → Request
 *
 * Features:
 * - Automatic request naming with [PREFIX] convention
 * - Variable detection (GLOBAL vs LOCAL)
 * - Test script generation
 * - Body generation with empty values
 * - Environment support
 *
 * @package Ronu\OpenApiGenerator\Services
 */
class PostmanCollectionGenerator
{
    protected string $environment;
    protected array $spec;
    protected string $collectionId;
    protected array $apiTypes;

    /**
     * Test template resolver
     */
    protected TestTemplateResolver $testResolver;

    public function __construct()
    {
        $this->collectionId = uniqid('postman-');
        $this->testResolver = app(TestTemplateResolver::class);
    }

    /**
     * Generate Postman Collection v2.1
     *
     * @param array $openApiSpec OpenAPI 3.0.3 specification
     * @param string $environment Environment name
     * @param array $apiTypes API types to include (empty = all)
     * @return array Postman collection structure
     */
    public function generate(
        array $openApiSpec,
        string $environment = 'artisan',
        array $apiTypes = []
    ): array {
        $this->spec = $openApiSpec;
        $this->environment = $environment;
        $this->apiTypes = $apiTypes;

        return [
            'info' => $this->buildInfo(),
            'item' => $this->buildFolderStructure(),
            'variable' => $this->buildCollectionVariables(),
            'auth' => $this->buildDefaultAuth(),
            'event' => $this->buildGlobalEvents(),
        ];
    }

    /**
     * Build collection info
     *
     * The title already comes formatted from OpenApiServices with the apiType suffix,
     * so we don't need to add it again here.
     *
     * @return array
     */
    protected function buildInfo(): array
    {
        $info = $this->spec['info'] ?? [];

        return [
            '_postman_id' => $this->collectionId,
            'name' => $info['title'] ?? 'Laravel API',
            'description' => $info['description'] ?? '',
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            '_exporter_id' => uniqid(),
        ];
    }

    /**
     * Build folder structure: API Types → Modules → Entities → Requests
     *
     * @return array
     */
    protected function buildFolderStructure(): array
    {
        $folders = [];
        $paths = $this->spec['paths'] ?? [];

        // Group by API Type → Module → Entity
        $grouped = $this->groupPaths($paths);

        // Build hierarchy
        foreach ($grouped as $apiType => $modules) {
            $apiFolder = $this->buildApiTypeFolder($apiType, $modules);
            $folders[] = $apiFolder;
        }

        return $folders;
    }

    /**
     * Group paths by API Type → Module → Entity
     *
     * @param array $paths OpenAPI paths
     * @return array Grouped paths
     */
    protected function groupPaths(array $paths): array
    {
        $grouped = [];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                // Extract metadata from operation
                $module = $operation['x-module'] ?? 'general';
                $entity = $operation['x-entity'] ?? 'resource';
                $apiType = $this->getApiTypeFromOperation($operation, $path);

                // Skip if not in filter
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
     * Get API type from operation
     *
     * @param array $operation OpenAPI operation
     * @param string $path Path URI
     * @return string API type key
     */
    protected function getApiTypeFromOperation(array $operation, string $path): string
    {
        // Try from tags
        if (isset($operation['tags'][0])) {
            $tag = $operation['tags'][0];
            if ($apiType = $operation['x-api-type'] ?? null) {
                return $apiType;
            }
        }

        // Try from path
        $pathParts = explode('/', trim($path, '/'));
        $prefix = $pathParts[0] ?? 'api';

        // Map prefix → API type
        $apiTypes = config('openapi.api_types', []);
        foreach ($apiTypes as $key => $config) {
            if ($config['prefix'] === $prefix) {
                return $key;
            }
        }

        return 'api';
    }

    /**
     * Build API Type folder
     *
     * @param string $apiType API type key
     * @param array $modules Modules data
     * @return array Folder structure
     */
    protected function buildApiTypeFolder(string $apiType, array $modules): array
    {
        $apiConfig = config("openapi.api_types.{$apiType}", []);

        $baseName = $apiConfig['folder_name'] ?? ucfirst($apiType);
        $folderName = "{$baseName}({$apiType})";

        $moduleItems = [];

        foreach ($modules as $module => $entities) {
            $moduleItems[] = $this->buildModuleFolder($module, $entities);
        }

        return [
            'name' => $folderName,
            'item' => $moduleItems,
            'description' => $apiConfig['description'] ?? '',
        ];
    }

    /**
     * Build Module folder
     *
     * @param string $module Module name
     * @param array $entities Entities data
     * @return array Folder structure
     */
    protected function buildModuleFolder(string $module, array $entities): array
    {
        $entityItems = [];

        foreach ($entities as $entity => $requests) {
            $entityItems[] = $this->buildEntityFolder($entity, $requests);
        }

        return [
            'name' => ucfirst($module),
            'item' => $entityItems,
        ];
    }

    /**
     * Build Entity folder
     *
     * @param string $entity Entity name
     * @param array $requests Requests data
     * @return array Folder structure
     */
    protected function buildEntityFolder(string $entity, array $requests): array
    {
        $requestItems = [];

        foreach ($requests as $request) {
            $requestItems[] = $this->buildRequest(
                $request['path'],
                $request['method'],
                $request['operation']
            );
        }

        return [
            'name' => ucfirst($entity),
            'item' => $requestItems,
        ];
    }

    /**
     * Build individual request
     *
     * @param string $path Path URI
     * @param string $method HTTP method
     * @param array $operation OpenAPI operation
     * @return array Request structure
     */
    protected function buildRequest(string $path, string $method, array $operation): array
    {
        $name = $operation['summary'] ?? $this->generateRequestName($path, $method);

        return [
            'name' => $name,
            'request' => [
                'method' => strtoupper($method),
                'header' => $this->buildHeaders($operation, $method),
                'body' => $this->buildRequestBody($operation, $method),
                'url' => $this->buildUrl($path, $operation),
                'description' => $operation['description'] ?? '',
            ],
            'response' => [],
            'event' => $this->buildRequestEvents($method, $operation),
        ];
    }

    /**
     * Build request URL with variables
     *
     * @param string $path Path URI
     * @param array $operation OpenAPI operation
     * @return array URL structure
     */
    protected function buildUrl(string $path, array $operation): array
    {
        $pathSegments = [];
        $pathVariables = [];

        $parts = explode('/', trim($path, '/'));

        foreach ($parts as $part) {
            if (preg_match('/\{([^}]+)\}/', $part, $matches)) {
                $paramName = $matches[1];

                // Get variable name from operation parameters
                $variableName = $this->getVariableNameForParameter(
                    $paramName,
                    $operation['parameters'] ?? []
                );

                $pathSegments[] = ':' . $paramName;
                $pathVariables[] = [
                    'key' => $paramName,
                    'value' => '{{' . $variableName . '}}',
                    'description' => $this->getParameterDescription($paramName, $operation['parameters'] ?? []),
                ];
            } else {
                $pathSegments[] = $part;
            }
        }

        return [
            'raw' => '{{base_url}}/' . implode('/', $pathSegments),
            'host' => ['{{base_url}}'],
            'path' => $pathSegments,
            'variable' => $pathVariables,
        ];
    }

    /**
     * Get variable name for parameter
     * Uses x-variable-name from OpenAPI if available
     *
     * @param string $paramName Parameter name
     * @param array $parameters Operation parameters
     * @return string Variable name
     */
    protected function getVariableNameForParameter(string $paramName, array $parameters): string
    {
        foreach ($parameters as $param) {
            if ($param['name'] === $paramName && isset($param['x-variable-name'])) {
                return $param['x-variable-name'];
            }
        }

        // Fallback to parameter name
        return $paramName;
    }

    /**
     * Get parameter description
     *
     * @param string $paramName Parameter name
     * @param array $parameters Operation parameters
     * @return string Description
     */
    protected function getParameterDescription(string $paramName, array $parameters): string
    {
        foreach ($parameters as $param) {
            if ($param['name'] === $paramName) {
                return $param['description'] ?? ucfirst($paramName);
            }
        }

        return ucfirst($paramName);
    }

    /**
     * Build request headers
     *
     * Already includes X-API-Key ✅
     *
     * @param array $operation OpenAPI operation
     * @param string $method HTTP method
     * @return array Headers array
     */
    protected function buildHeaders(array $operation, string $method): array
    {
        $headers = [
            [
                'key' => 'X-API-Key',
                'value' => '{{api_key}}',
                'type' => 'text',
            ],
            [
                'key' => 'Accept',
                'value' => 'application/json',
                'type' => 'text',
            ],
        ];

        // Content-Type for POST/PUT/PATCH
        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $headers[] = [
                'key' => 'Content-Type',
                'value' => 'application/json',
                'type' => 'text',
            ];
        }

        // Authorization if requires security (disabled by default)
        if (!empty($operation['security'])) {
            $headers[] = [
                'key' => 'Authorization',
                'value' => 'Bearer {{token}}',
                'type' => 'text',
                'disabled' => true,
            ];
        }

        return $headers;
    }

    /**
     * Build request body with EMPTY values
     *
     * @param array $operation OpenAPI operation
     * @param string $method HTTP method
     * @return array|null Body structure
     */
    protected function buildRequestBody(array $operation, string $method): ?array
    {
        if (!in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            return null;
        }

        $requestBody = $operation['requestBody'] ?? null;

        if (!$requestBody) {
            return null;
        }

        $schema = $requestBody['content']['application/json']['schema'] ?? null;

        if (!$schema) {
            return null;
        }

        // Generate example with EMPTY values
        $example = $this->generateEmptyExample($schema);

        return [
            'mode' => 'raw',
            'raw' => json_encode($example, JSON_PRETTY_PRINT),
            'options' => [
                'raw' => [
                    'language' => 'json',
                ],
            ],
        ];
    }

    /**
     * Generate example from JSON schema with EMPTY values
     *
     * @param array $schema JSON schema
     * @return array|mixed Example data
     */
    protected function generateEmptyExample(array $schema)
    {
        if ($schema['type'] === 'object' && isset($schema['properties'])) {
            $example = [];

            foreach ($schema['properties'] as $field => $fieldSchema) {
                $example[$field] = $this->getEmptyValue($fieldSchema);
            }

            return $example;
        }

        return $this->getEmptyValue($schema);
    }

    /**
     * Get empty value for schema type
     *
     * @param array $schema JSON schema
     * @return mixed Empty value
     */
    protected function getEmptyValue(array $schema)
    {
        $type = $schema['type'] ?? 'string';

        return match ($type) {
            'string' => '',
            'integer' => 0,
            'number' => 0.0,
            'boolean' => false,
            'array' => [],
            'object' => new \stdClass(),
            default => null,
        };
    }

    /**
     * Build request-specific events (tests)
     *
     * @param string $method HTTP method
     * @param array $operation OpenAPI operation
     * @return array Events array
     */
    protected function buildRequestEvents(string $method, array $operation): array
    {
        $action = $operation['x-action-type'] ?? 'unknown';
        $entity = $operation['x-entity'] ?? 'resource';

        $tests = $this->testResolver->generatePostmanTest($action, $entity);

        if (empty($tests)) {
            return [];
        }

        return [
            [
                'listen' => 'test',
                'script' => [
                    'exec' => $tests,
                    'type' => 'text/javascript',
                ],
            ],
        ];
    }

    /**
     * Build collection-level variables
     *
     * @return array Variables array
     */
    protected function buildCollectionVariables(): array
    {
        // Collection variables are just references
        // Actual values are in environments
        return [
            [
                'key' => 'base_url',
                'value' => 'http://localhost:8000',
                'type' => 'default',
            ],
        ];
    }

    /**
     * Build default auth
     *
     * @return array Auth structure
     */
    protected function buildDefaultAuth(): array
    {
        return [
            'type' => 'bearer',
            'bearer' => [
                [
                    'key' => 'token',
                    'value' => '{{token}}',
                    'type' => 'string',
                ],
            ],
        ];
    }

    /**
     * Build global events
     *
     * @return array Events array
     */
    protected function buildGlobalEvents(): array
    {
        return [
            [
                'listen' => 'prerequest',
                'script' => [
                    'type' => 'text/javascript',
                    'exec' => [
                        '// Global pre-request',
                        'console.log("📤 " + pm.request.method + " " + pm.request.url);',
                    ],
                ],
            ],
            [
                'listen' => 'test',
                'script' => [
                    'type' => 'text/javascript',
                    'exec' => [
                        '// Global test',
                        'console.log("📥 " + pm.response.code + " " + pm.response.status);',
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate request name fallback
     *
     * @param string $path Path URI
     * @param string $method HTTP method
     * @return string Request name
     */
    protected function generateRequestName(string $path, string $method): string
    {
        $parts = explode('/', trim($path, '/'));
        $resource = end($parts);

        return strtoupper($method) . ' ' . $resource;
    }
}