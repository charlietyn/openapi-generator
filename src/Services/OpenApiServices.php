<?php

namespace Ronu\OpenApiGenerator\Services;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionClass;
use Ronu\OpenApiGenerator\Helpers\PlaceholderHelper;

/**
 * OpenAPI Specification Generator Service - Version 2.1 FIXED
 *
 * CRITICAL FIXES:
 * - ISSUE #1: Proper route naming (module.entity.action)
 * - Improved action detection from URI
 * - Better handling of named vs unnamed routes
 *
 * @package App\Services
 */
class OpenApiServices
{
    protected array $spec;
    protected string $title;
    protected array $paths = [];
    protected array $tags = [];
    protected array $components = [];
    protected ?array $apiTypeFilter = null;
    protected array $structureCache = [];
    protected DocumentationResolver $docResolver;

    protected ?string $environment = null;

    protected PostmanCollectionGenerator $postmanGenerator;
    protected InsomniaWorkspaceGenerator $insomniaGenerator;
    protected EnvironmentGenerator $environmentGenerator;

    /**
     * Initialize the generator
     */
    public function __construct()
    {
        $this->title = PlaceholderHelper::getProjectName();
        $this->initializeSpec();
        $this->postmanGenerator = new PostmanCollectionGenerator();
        $this->insomniaGenerator = new InsomniaWorkspaceGenerator();
        $this->environmentGenerator = new EnvironmentGenerator();
        $this->docResolver = app(DocumentationResolver::class);
    }

    /**
     * Set API types to filter
     *
     * @param array $types Array of API type keys (e.g., ['api', 'movile'])
     * @return self
     */
    public function setApiTypeFilter(array $types): self
    {
        $this->apiTypeFilter = $types;

        Log::channel('openapi')->info('API type filter set', [
            'types' => $types,
            'available_types' => array_keys(config('openapi.api_types')),
        ]);

        return $this;
    }

    /**
     * Generate specification with filters
     */
    public function generate(
        bool    $useCache = true,
        ?array  $apiTypes = null,
        ?string $environment = null,
        string  $format = 'openapi'
    ): array
    {
        $this->apiTypeFilter = $apiTypes;
        $this->environment = $environment ?? config('openapi.default_environment');

        $cacheKey = $this->buildCacheKey($apiTypes, $environment, $format);

        if ($useCache && config('openapi.cache.enabled')) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $this->inspectRoutes();
        $this->spec['paths'] = $this->paths;
        $this->spec['tags'] = array_values($this->tags);
        $this->spec['components'] = $this->components;

        if ($environment) {
            $this->applyEnvironment($environment);
        }

        $result = $this->convertToFormat($this->spec, $format, $environment);

        if (config('openapi.cache.enabled')) {
            Cache::put($cacheKey, $result, config('openapi.cache.ttl'));
        }

        return $result;
    }

    /**
     * Build cache key with filters
     */
    protected function buildCacheKey(?array $apiTypes, ?string $environment, string $format): string
    {
        $prefix = config('openapi.cache.key_prefix');
        $apiTypesStr = $apiTypes ? implode('_', $apiTypes) : 'all';
        $envStr = $environment ?? 'default';

        return "{$prefix}{$apiTypesStr}_{$envStr}_{$format}";
    }

    /**
     * Convert OpenAPI spec to requested format
     */
    protected function convertToFormat(array $spec, string $format, ?string $environment): array
    {
        return match ($format) {
            'postman' => $this->postmanGenerator->generate($spec, $environment ?? 'artisan'),
            'insomnia' => $this->insomniaGenerator->generate($spec, $environment ?? 'artisan'),
            default => $spec,
        };
    }

    /**
     * Apply environment configuration to spec
     */
    protected function applyEnvironment(string $environment): void
    {
        $envConfig = config("openapi.environments.{$environment}");

        if ($envConfig) {
            $this->spec['servers'] = [
                [
                    'url' => $envConfig['base_url'],
                    'description' => $envConfig['name'],
                ],
            ];
        }
    }

    /**
     * Initialize the base OpenAPI specification structure
     */
    protected function initializeSpec(): void
    {
        $this->spec = [
            'openapi' => '3.0.3',
            'info' => $this->buildDynamicInfo(),
            'servers' => $this->buildDynamicServers(),
            'paths' => [],
        ];
    }

    /**
     * Build dynamic info with placeholder replacement
     */
    protected function buildDynamicInfo(): array
    {
        return [
            'title' => $this->title,
            'description' => 'Complete API documentation for all application modules',
            'version' => env('API_VERSION', '1.0.0'),
            'contact' => [
                'name' => 'API Support Team',
                'email' => PlaceholderHelper::generateEmail('support'),
                'url' => PlaceholderHelper::generateUrl('https://' . strtolower($this->title) . '.com/support'),
            ],
            'license' => [
                'name' => 'MIT',
                'url' => 'https://opensource.org/licenses/MIT',
            ],
        ];
    }

    /**
     * Build dynamic servers with placeholder replacement
     */
    protected function buildDynamicServers(): array
    {
        return PlaceholderHelper::replace([
            [
                'url' => 'http://127.0.0.1:8000',
                'description' => 'Artisan server',
            ],
            [
                'url' => 'https://localhost/' . $this->title,
                'description' => 'Local Server',
            ],
            [
                'url' => 'https://' . $this->title . '.com',
                'description' => 'Production Server',
            ],
        ]);
    }

    /**
     * Check if route is a module root route (should be excluded)
     */
    protected function isModuleRootRoute(string $uri): bool
    {
        $parts = explode('/', trim($uri, '/'));
        $nonParams = array_filter($parts, fn($p) => !Str::startsWith($p, '{'));
        $nonParams = array_values($nonParams);

        if (count($nonParams) === 2 && $this->isNwidartModule($nonParams[1])) {
            Log::channel('openapi')->debug('Module root route detected', [
                'uri' => $uri,
                'segments' => $nonParams,
                'module'=> $nonParams[1],
                'reason' => 'Only prefix + module, no entity',
            ]);
            return true;
        }

        return false;
    }

    /**
     * Check if endpoint is authentication related
     */
    protected function isAuthEndpoint(string $uri, string $action): bool
    {
        $authPatterns = [
            'login',
            'register',
            'logout',
            'refresh',
            'verify',
            'permissions',
            'user-profile',
            'password/reset',
            'password/forgot',
            'email/verify',
            'auth/login',
            'auth/register',
        ];

        $lowerUri = strtolower($uri);
        $lowerAction = strtolower($action);

        foreach ($authPatterns as $pattern) {
            if (str_contains($lowerUri, $pattern) || $lowerAction === $pattern) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get global security requirements
     */
    protected function getGlobalSecurity(): array
    {
        return [
            ['BearerAuth' => []],
            ['ApiKeyAuth' => []],
        ];
    }

    /**
     * Get default component schemas
     */
    protected function getDefaultSchemas(): array
    {
        return [
            'Error' => [
                'type' => 'object',
                'properties' => [
                    'message' => [
                        'type' => 'string',
                        'example' => 'Error message',
                    ],
                ],
            ],
            'ValidationError' => [
                'type' => 'object',
                'properties' => [
                    'message' => [
                        'type' => 'string',
                        'example' => 'The given data was invalid.',
                    ],
                    'errors' => [
                        'type' => 'object',
                        'additionalProperties' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Inspect all application routes with filtering
     */
    protected function inspectRoutes(): void
    {
        $routes = Route::getRoutes();
        $excludePatterns = config('openapi.exclude_routes', []);

        foreach ($routes as $route) {
            $uri = $route->uri();

            if ($this->shouldExcludeRoute($uri, $excludePatterns)) {
                continue;
            }

            if (!$this->isApiRoute($uri)) {
                continue;
            }

            if ($this->apiTypeFilter && !$this->matchesApiTypeFilter($uri)) {
                continue;
            }

            if ($this->shouldExcludeModule($uri)) {
                continue;
            }

            $this->processRoute($route);
        }
    }

    /**
     * Find model class for entity name (tries both singular and plural variants)
     */
    private function findModelClass(string $entity, string $namespace = 'App\\Models'): ?string
    {
        $singularClass = Str::studly(Str::singular($entity));
        $singularFqcn = "{$namespace}\\{$singularClass}";

        $originalClass = Str::studly($entity);
        $originalFqcn = "{$namespace}\\{$originalClass}";

        if (class_exists($singularFqcn)) {
            Log::channel('openapi')->debug('Model found (singular variant)', [
                'entity' => $entity,
                'class' => $singularFqcn,
                'namespace' => $namespace,
            ]);
            return $singularFqcn;
        }

        if ($originalClass !== $singularClass && class_exists($originalFqcn)) {
            Log::channel('openapi')->debug('Model found (original/plural variant)', [
                'entity' => $entity,
                'class' => $originalFqcn,
                'namespace' => $namespace,
            ]);
            return $originalFqcn;
        }

        Log::channel('openapi')->debug('Model not found in namespace', [
            'entity' => $entity,
            'namespace' => $namespace,
            'tried_singular' => $singularFqcn,
            'tried_original' => $originalFqcn,
        ]);

        return null;
    }

    /**
     * Check if entity name corresponds to a global model (App\Models)
     */
    protected function isGlobalEntityModel(string $entity): bool
    {
        $modelClass = $this->findModelClass($entity, 'App\\Models');

        if ($modelClass) {
            Log::channel('openapi')->debug('Global model detected', [
                'entity' => $entity,
                'class' => $modelClass,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Check if route should be excluded from documentation
     */
    protected function shouldExcludeRoute(string $uri, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $uri)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if segment is a valid Nwidart module
     */
    protected function isNwidartModule(string $segment): bool
    {
        if (!$segment) {
            return false;
        }

        $moduleName = Str::studly($segment);
        $modulesPath = config('openapi.modules_path', base_path('Modules'));
        $moduleDir = "{$modulesPath}/{$moduleName}";

        if (is_dir($moduleDir)) {
            Log::channel('openapi')->debug('Nwidart module found', [
                'segment' => $segment,
                'module_name' => $moduleName,
                'path' => $moduleDir,
            ]);
            return true;
        }

        Log::channel('openapi')->debug('Not a Nwidart module', [
            'segment' => $segment,
            'expected_path' => $moduleDir,
        ]);

        return false;
    }

    /**
     * Parse URI structure with proper Nwidart module detection
     */
    protected function parseUriStructure(string $uri): array
    {
        if (isset($this->structureCache[$uri])) {
            return $this->structureCache[$uri];
        }

        $parts = explode('/', trim($uri, '/'));
        $nonParams = array_filter($parts, fn($p) => !Str::startsWith($p, '{'));
        $nonParams = array_values($nonParams);
        $lastPart = end($nonParams);

        $prefix = $nonParams[0] ?? 'api';
        $secondSegment = $nonParams[1] ?? null;
        $segmentCount = count($nonParams);

        // PRIORITY 1: Is segment2 a Nwidart MODULE?
        if ($secondSegment && $this->isNwidartModule($secondSegment)) {
            if ($segmentCount >= 3) {
                $module = $nonParams[1];
                $entity = $nonParams[2];

                $structure = [
                    'prefix' => $prefix,
                    'module' => $module,
                    'entity' => $entity,
                    'params' => array_slice($parts, $segmentCount),
                ];

                Log::channel('openapi')->info('Nwidart modular structure detected', [
                    'uri' => $uri,
                    'module' => $module,
                    'entity' => $entity,
                ]);
            } else {
                $structure = [
                    'prefix' => $prefix,
                    'module' => $secondSegment,
                    'entity' => 'resource',
                    'params' => array_slice($parts, $segmentCount),
                ];
            }
        }
        // PRIORITY 2: Is segment2 a GLOBAL ENTITY?
        else if ($secondSegment && $this->isGlobalEntityModel($secondSegment)) {
            $structure = [
                'prefix' => $prefix,
                'module' => 'general',
                'entity' => $secondSegment,
                'params' => array_slice($parts, $segmentCount),
            ];

            Log::channel('openapi')->info('Global entity detected (not a module)', [
                'uri' => $uri,
                'entity' => $secondSegment,
                'module' => 'general',
            ]);
        }
        // PRIORITY 3: CUSTOM URIs (auth endpoints)
        else if ($this->isAuthEndpoint($uri, $lastPart)) {
            $structure = [
                'prefix' => $prefix,
                'module' => 'general',
                'entity' => 'auth',
                'params' => array_slice($parts, $segmentCount),
            ];
        }
        // FALLBACK
        else {
            $structure = [
                'prefix' => $prefix,
                'module' => 'general',
                'entity' => $secondSegment ?? 'resource',
                'params' => array_slice($parts, $segmentCount),
            ];

            Log::channel('openapi')->warning('Unknown URI structure - using fallback', [
                'uri' => $uri,
                'segments' => $nonParams,
            ]);
        }

        $this->structureCache[$uri] = $structure;
        return $structure;
    }

    /**
     * Check if module should be excluded
     */
    protected function shouldExcludeModule(string $uri): bool
    {
        $parts = explode('/', trim($uri, '/'));

        if (count($parts) < 2) {
            return false;
        }

        $prefix = $parts[0];
        $module = $parts[1];

        $excludeModules = config('openapi.exclude_modules', []);
        if (in_array($module, $excludeModules)) {
            return true;
        }

        $excludeModuleRoutes = config('openapi.exclude_module_routes', []);
        if (isset($excludeModuleRoutes[$prefix])) {
            if (in_array($module, $excludeModuleRoutes[$prefix])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if route is an API route and matches filter
     */
    protected function isApiRoute(string $uri): bool
    {
        $apiPrefixes = array_column(config('openapi.api_types'), 'prefix');

        $isApi = false;
        foreach ($apiPrefixes as $prefix) {
            if (Str::startsWith($uri, $prefix . '/') || $uri === $prefix) {
                $isApi = true;
                break;
            }
        }

        if (!$isApi) {
            return false;
        }

        return $this->matchesApiTypeFilter($uri);
    }

    /**
     * Check if route matches API type filter
     */
    protected function matchesApiTypeFilter(string $uri): bool
    {
        if (empty($this->apiTypeFilter)) {
            return true;
        }

        $apiTypes = config('openapi.api_types');

        foreach ($this->apiTypeFilter as $filterType) {
            if (!isset($apiTypes[$filterType])) {
                Log::channel('openapi')->warning('Unknown API type in filter', ['type' => $filterType]);
                continue;
            }

            $prefix = $apiTypes[$filterType]['prefix'];

            if (Str::startsWith($uri, $prefix . '/') || $uri === $prefix) {
                Log::channel('openapi')->debug('Route matched filter', [
                    'uri' => $uri,
                    'filter' => $filterType,
                    'prefix' => $prefix,
                ]);
                return true;
            }
        }

        Log::channel('openapi')->debug('Route filtered out', [
            'uri' => $uri,
            'filter' => $this->apiTypeFilter,
        ]);

        return false;
    }

    /**
     * Process a single route and add to specification
     */
    protected function processRoute($route): void
    {
        $methods = $route->methods();
        $uri = '/' . $route->uri();
        $action = $route->getAction();

        if ($this->isModuleRootRoute($uri)) {
            Log::channel('openapi')->info('Skipping module root route', [
                'uri' => $uri,
                'reason' => 'No entity specified',
            ]);
            return;
        }

        $methods = array_filter($methods, fn($m) => !in_array($m, ['HEAD', 'OPTIONS']));

        foreach ($methods as $method) {
            $method = strtolower($method);
            if (!isset($this->paths[$uri])) {
                $this->paths[$uri] = [];
            }
            $this->paths[$uri][$method] = $this->buildOperation($route, $method);
        }
    }

    /**
     * Check if entity is global
     */
    protected function isGlobalEntity(string $entity, string $module): bool
    {
        $singularClass = Str::studly(Str::singular($entity));
        $originalClass = Str::studly($entity);

        $modulePatterns = [
            "Modules\\{$module}\\Entities\\{$singularClass}",
            "Modules\\{$module}\\Models\\{$singularClass}",
            "Modules\\{$module}\\Entities\\{$originalClass}",
            "Modules\\{$module}\\Models\\{$originalClass}",
        ];

        foreach ($modulePatterns as $className) {
            if (class_exists($className)) {
                Log::channel('openapi')->debug('Entity found in module (not global)', [
                    'entity' => $entity,
                    'module' => $module,
                    'class' => $className,
                ]);
                return false;
            }
        }

        $globalClass = $this->findModelClass($entity, 'App\\Models');

        if ($globalClass) {
            Log::channel('openapi')->info('Global entity detected (should be in General)', [
                'entity' => $entity,
                'module' => $module,
                'global_class' => $globalClass,
            ]);
            return true;
        }

        Log::channel('openapi')->debug('Entity not found anywhere', [
            'entity' => $entity,
            'module' => $module,
        ]);

        return false;
    }

    /**
     * Extract controller class from route action
     *
     * Laravel stores controller actions as "ClassName@methodName"
     * This method extracts only the class name for use with class_exists() and ReflectionClass
     *
     * @param array $action Route action array
     * @return string|null Controller class name (without @method) or null
     */
    protected function extractControllerClass(array $action): ?string
    {
        $controllerAction = $action['controller'] ?? null;

        if (!$controllerAction) {
            return null;
        }

        // If it's a string containing "@method", extract only the class
        if (is_string($controllerAction) && str_contains($controllerAction, '@')) {
            [$className, $methodName] = explode('@', $controllerAction, 2);

            Log::channel('openapi')->debug('Controller class extracted from action string', [
                'original' => $controllerAction,
                'class' => $className,
                'method' => $methodName,
            ]);

            return $className;
        }

        // If it's already a class name (no @), return as is
        if (is_string($controllerAction)) {
            return $controllerAction;
        }

        // If it's a closure or invokable, return null
        return null;
    }

    /**
     * Build operation definition for a route
     */
    protected function buildOperation($route, string $method): array
    {
        $action = $route->getAction();
        $uri = $route->uri();

        $structure = $this->parseUriStructure($uri);
        $prefix = $structure['prefix'];
        $module = $structure['module'];
        $entity = $structure['entity'];

        $routeAction = $this->extractAction($action, $method);

        $requestName = $this->generateRequestName($route, $prefix, $structure, $routeAction);

        $controller = $this->extractControllerClass($action);

        $documentation = $this->docResolver->resolveForOperation(
            $entity,
            $routeAction,
            $controller,
            $route
        );

        $operationId = $requestName['technical_name'];

        $tag = $this->getOrCreateTag($module, $prefix);

        $operation = [
            'operationId' => $operationId,
            'summary' => $requestName['display_name'],
            'description' => $documentation['description'] ?? $requestName['description'],
            'tags' => [$tag],
            'parameters' => $this->extractParameters($route),
            'responses' => $this->buildResponses($method, $routeAction),
            'x-module' => $module,
            'x-entity' => $entity,
            'x-action-type' => $routeAction,
        ];

        $security = $this->extractSecurity($route);
        if (!empty($security)) {
            $operation['security'] = $security;
        }

        if (in_array($method, ['post', 'put', 'patch'])) {
            $requestBody = $this->buildRequestBody(
                $controller,
                $routeAction,
                $documentation
            );
            if ($requestBody) {
                $operation['requestBody'] = $requestBody;
            }
        }

        return $operation;
    }

    /**
     * Apply documentation template to operation
     */
    protected function applyDocumentationTemplate(string $action, string $resource): array
    {
        $templates = config('openapi_templates.resource_descriptions', []);
        $resourceName = Str::plural(Str::title(str_replace('-', ' ', $resource)));

        if (isset($templates[$action])) {
            $template = $templates[$action];

            return [
                'summary' => str_replace('{resource}', $resourceName, $template['summary']),
                'description' => str_replace('{resource}', $resourceName, $template['description']),
            ];
        }

        return [
            'summary' => $this->generateSummary($action, $resource),
            'description' => $this->generateDescription($action, $resource),
        ];
    }

    /**
     * Extract API prefix from URI
     */
    protected function extractPrefix(string $uri): string
    {
        $apiTypes = config('openapi.api_types');
        foreach ($apiTypes as $key => $config) {
            if (Str::startsWith($uri, $config['prefix'])) {
                return $key;
            }
        }
        return 'api';
    }

    /**
     * Extract resource name from URI
     */
    protected function extractResource(string $uri): string
    {
        $parts = explode('/', trim($uri, '/'));
        $parts = array_filter($parts, fn($p) => !Str::startsWith($p, '{'));

        if (count($parts) >= 2) {
            return Str::singular(Str::kebab($parts[1]));
        }

        return 'resource';
    }

    /**
     * Get variable name for parameter (GLOBAL vs LOCAL)
     *
     * Golden Rule:
     * - GLOBAL: Actions CRUD (show, update, destroy) + resource in tracking_variables → last_{resource}_id
     * - LOCAL: Other actions or non-ID params → direct name
     */
    protected function getVariableName(string $paramName, string $uri, string $actionType): string
    {
        $structure = $this->parseUriStructure($uri);
        $resourceName = $structure['entity'];

        $crudActions = ['show', 'update', 'destroy', 'edit'];
        $isCrudAction = in_array(strtolower($actionType), $crudActions);

        $trackingVars = config('openapi.environments.base.tracking_variables', []);
        $globalVarName = 'last_' . Str::snake($resourceName) . '_id';
        $hasTracking = array_key_exists($globalVarName, $trackingVars);

        $isIdParam = in_array($paramName, ['id', $resourceName . '_id', Str::snake($resourceName) . '_id']);

        if ($isCrudAction && $hasTracking && $isIdParam) {
            Log::channel('openapi')->debug('Using GLOBAL variable', [
                'param' => $paramName,
                'variable' => $globalVarName,
                'action' => $actionType,
                'resource' => $resourceName,
            ]);
            return $globalVarName;
        }

        Log::channel('openapi')->debug('Using LOCAL variable', [
            'param' => $paramName,
            'action' => $actionType,
            'resource' => $resourceName,
            'is_crud' => $isCrudAction,
            'has_tracking' => $hasTracking,
        ]);

        return $paramName;
    }

    /**
     * ✅ FIXED: Generate request name following convention: [PREFIX] module.entity.action
     *
     * CRITICAL FIX - ISSUE #1:
     * - Properly constructs module.entity.action even for named routes
     * - Detects action from URI for unnamed routes
     * - Handles global entities correctly (module = "general")
     *
     * Examples:
     * - Named route "users" on /security/users → security.users.index
     * - Unnamed route POST /users/validate → general.users.validate
     * - Unnamed route POST /api-apps/{id}/rotate → general.api-apps.rotate
     */
    protected function generateRequestName($route, string $prefix, array $structure, string $actionType): array
    {
        $routeName = $route->getName();
        $uri = $route->uri();
        $module = $structure['module'];
        $entity = $structure['entity'];
        $method = $route->methods()[0] ?? 'GET';

        // ✅ CRITICAL FIX: Detect final action properly
        $finalAction = $this->detectActionFromRoute($route, $uri, $entity, $actionType, $routeName);

        // ✅ CRITICAL FIX: Build proper technical name (module.entity.action)
        $technicalName = $this->buildTechnicalName($module, $entity, $finalAction);

        // ✅ Build display name with prefix
        $displayName = '[' . strtoupper($prefix) . '] ' . $technicalName;

        // ✅ Description
        $description = $routeName
            ? "Named route: {$routeName} | Method: {$method} | Path: {$uri}"
            : "Method: {$method} | Path: {$uri}";

        Log::channel('openapi')->info('Request name generated', [
            'uri' => $uri,
            'route_name' => $routeName,
            'module' => $module,
            'entity' => $entity,
            'detected_action' => $finalAction,
            'technical_name' => $technicalName,
            'display_name' => $displayName,
        ]);

        return [
            'display_name' => $displayName,
            'technical_name' => $technicalName,
            'description' => $description,
        ];
    }

    /**
     * ✅ NEW: Detect action from route with priority system
     *
     * Priority:
     * 1. URI endpoint (custom actions like /validate, /rotate, /bulk_create)
     * 2. Named route last segment (if available)
     * 3. Detected action type (from controller method)
     */
    protected function detectActionFromRoute($route, string $uri, string $entity, string $actionType, ?string $routeName): string
    {
        // PRIORITY 1: Check for custom action in URI
        $customAction = $this->extractActionFromUri($uri, $entity, $actionType);
        if ($customAction !== $actionType) {
            Log::channel('openapi')->debug('Custom action detected from URI', [
                'uri' => $uri,
                'custom_action' => $customAction,
                'default_action' => $actionType,
            ]);
            return $customAction;
        }

        // PRIORITY 2: If route has name, extract action from it
        if ($routeName) {
            $parts = explode('.', $routeName);
            $routeAction = end($parts);

            // Map Laravel Resource actions to our convention
            $actionMap = [
                'index' => 'list',
                'store' => 'create',
                'show' => 'show',
                'update' => 'update',
                'destroy' => 'delete',
                'edit' => 'edit',
            ];

            $mappedAction = $actionMap[$routeAction] ?? $routeAction;

            Log::channel('openapi')->debug('Action detected from route name', [
                'route_name' => $routeName,
                'route_action' => $routeAction,
                'mapped_action' => $mappedAction,
            ]);
            if (empty($mappedAction)) {
                $mappedAction = $this->extractAction($route->getAction(), $route->methods()[0] ?? 'GET');;
            }
            return $mappedAction;
        }

        // PRIORITY 3: Use detected action type
        return $actionType;
    }

    /**
     * ✅ NEW: Build technical name (module.entity.action)
     *
     * Handles special cases:
     * - If module is "general", can omit it or keep it
     * - Ensures consistent format
     */
    protected function buildTechnicalName(string $module, string $entity, string $action): string
    {
        // For now, always include module (even "general")
        // This ensures consistency
        return implode('.', [$module, $entity, $action]);
    }

    /**
     * Extract action name from controller action
     */
    protected function extractAction($action, string $method): string
    {
        if (is_string($action['uses'] ?? null) && Str::contains($action['uses'], '@')) {
            [, $actionMethod] = explode('@', $action['uses']);
            return $this->mapActionToOperation($actionMethod, $method);
        }

        return $this->mapMethodToAction($method);
    }

    /**
     * Map controller action to operation type
     */
    protected function mapActionToOperation(string $action, string $method): string
    {
        $mapping = [
            'index' => 'list',
            'store' => 'create',
            'show' => 'show',
            'update' => 'update',
            'destroy' => 'delete',
        ];

//        return $mapping[$action] ?? Str::kebab($action);
        return $mapping[$action] ?? $action;
    }

    /**
     * Map HTTP method to action type
     */
    protected function mapMethodToAction(string $method): string
    {
        $mapping = [
            'get' => 'list',
            'post' => 'create',
            'put' => 'update',
            'patch' => 'update',
            'delete' => 'delete',
        ];

        return $mapping[$method] ?? 'action';
    }

    /**
     * Generate operation ID following the pattern: {prefix}-{resource}-{action}
     */
    protected function generateOperationId(string $prefix, string $resource, string $action): string
    {
        return implode('-', [$prefix, $resource, $action]);
    }

    /**
     * Generate summary for operation
     */
    protected function generateSummary(string $action, string $resource): string
    {
        $actionText = [
            'list' => 'List all',
            'create' => 'Create a new',
            'show' => 'Get',
            'update' => 'Update',
            'delete' => 'Delete',
        ];

        $text = $actionText[$action] ?? ucfirst($action);
        $resourceName = Str::plural(Str::title(str_replace('-', ' ', $resource)));

        return "{$text} {$resourceName}";
    }

    /**
     * Generate description for operation
     */
    protected function generateDescription(string $action, string $resource): string
    {
        $descriptions = [
            'list' => 'Retrieve a paginated list of all %s resources',
            'create' => 'Create a new %s resource with the provided data',
            'show' => 'Retrieve detailed information about a specific %s resource',
            'update' => 'Update an existing %s resource with new data',
            'delete' => 'Permanently delete a %s resource',
        ];

        $resourceName = str_replace('-', ' ', $resource);

        if (isset($descriptions[$action])) {
            return sprintf($descriptions[$action], $resourceName);
        }

        return sprintf('Perform %s operation on %s resource', $action, $resourceName);
    }

    /**
     * Get or create tag for resource grouping
     */
    protected function getOrCreateTag(string $resource, string $prefix): string
    {
        $resource = ucfirst(Str::camel($resource));
        $tagName = $resource;

        if (!isset($this->tags[$tagName])) {
            $apiConfig = config("openapi.api_types.{$prefix}", []);

            $this->tags[$tagName] = [
                'name' => $tagName,
                'description' => "{$resource} management endpoints",
                'x-api-type' => $prefix,
                'x-display-name' => $apiConfig['folder_name'] ?? ucfirst($prefix),
            ];
        }

        return $tagName;
    }

    /**
     * Extract route parameters from URI
     */
    protected function extractParameters($route): array
    {
        $parameters = [];
        $uri = $route->uri();
        $action = $route->getAction();
        $actionType = $this->extractAction($action, $route->methods()[0] ?? 'GET');

        preg_match_all('/\{([^}]+)\}/', $uri, $matches);

        foreach ($matches[1] as $param) {
            $isOptional = Str::endsWith($param, '?');
            $paramName = rtrim($param, '?');

            $variableName = $this->getVariableName($paramName, $uri, $actionType);

            $parameters[] = [
                'name' => $paramName,
                'in' => 'path',
                'required' => !$isOptional,
                'description' => ucfirst($paramName) . ' identifier',
                'schema' => $this->inferParameterType($paramName),
                'x-variable-name' => $variableName,
            ];
        }
        return $parameters;
    }

    /**
     * Infer parameter type from parameter name
     */
    protected function inferParameterType(string $name): array
    {
        if (Str::endsWith($name, '_id') || $name === 'id') {
            return ['type' => 'integer', 'format' => 'int64', 'example' => 1];
        }

        if (Str::contains($name, 'uuid')) {
            return ['type' => 'string', 'format' => 'uuid', 'example' => '550e8400-e29b-41d4-a716-446655440000'];
        }

        if (Str::endsWith($name, 'slug')) {
            return ['type' => 'string', 'pattern' => '^[a-z0-9-]+$', 'example' => 'example-slug'];
        }

        return ['type' => 'string', 'example' => 'value'];
    }

    /**
     * Extract security requirements from route middleware
     */
    protected function extractSecurity($route): array
    {
        $middleware = $route->middleware();
        $security = [];
        $middlewareMap = config('openapi.middleware_security_map');

        foreach ($middleware as $mw) {
            if (isset($middlewareMap[$mw])) {
                foreach ($middlewareMap[$mw] as $scheme) {
                    $security[] = [$scheme => []];
                }
            }
        }

        return $security;
    }

    /**
     * Build responses definition for operation
     */
    protected function buildResponses(string $method, string $action): array
    {
        $responses = [];
        $responseExamples = config('openapi.response_examples');

        if ($method === 'post' && $action === 'create') {
            $responses['201'] = $responseExamples['201'];
        } else {
            $responses['200'] = $responseExamples['200'];
        }

        $responses['401'] = $responseExamples['401'];
        $responses['403'] = $responseExamples['403'];

        if (Str::contains($action, ['show', 'update', 'delete'])) {
            $responses['404'] = $responseExamples['404'];
        }

        if (in_array($method, ['post', 'put', 'patch'])) {
            $responses['422'] = $responseExamples['422'];
        }

        $responses['500'] = $responseExamples['500'];

        return $responses;
    }

    /**
     * Build request body definition for operation
     *
     * Priority:
     * 1. Use documentation from MetadataExtractor (FormRequest + Model)
     * 2. Generic fallback
     *
     * @param string|null $controller Controller class
     * @param string $action Action name
     * @param array $documentation Documentation from resolver
     * @return array|null Request body definition
     */
    protected function buildRequestBody(
        ?string $controller,
        string $action,
        array $documentation = []
    ): ?array {
        // ==========================================
        // COMPREHENSIVE LOGGING POINT
        // ==========================================

        Log::channel('openapi')->info('[buildRequestBody] Starting request body generation', [
            'action' => $action,
            'controller' => $controller ? class_basename($controller) : 'none',
            'has_documentation' => !empty($documentation),
            'documentation_keys' => array_keys($documentation),
        ]);

        // ==========================================
        // DATA QUALITY INSPECTION
        // ==========================================

        if (isset($documentation['request_example'])) {
            $exampleData = $documentation['request_example'];
            $exampleFields = array_keys($exampleData);
            $exampleCount = count($exampleData);

            Log::channel('openapi')->info('[buildRequestBody] Request example found', [
                'fields_count' => $exampleCount,
                'fields' => $exampleFields,
                'is_empty' => $exampleCount === 0,
                'is_generic' => $exampleCount === 1 && isset($exampleData['data']),
                'sample' => array_slice($exampleData, 0, 3), // First 3 fields
            ]);

            if ($exampleCount > 0 && !($exampleCount === 1 && isset($exampleData['data']))) {
                Log::channel('openapi')->info('[buildRequestBody] ✅ Using FormRequest/Model data', [
                    'source' => $documentation['form_request_class'] ?? 'Model fallback',
                    'scenario' => $documentation['scenario'] ?? 'none',
                    'fields' => $exampleFields,
                ]);

                return [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => $documentation['request_schema'] ?? [
                                    'type' => 'object',
                                    'properties' => [],
                                ],
                            'example' => $exampleData,
                        ],
                    ],
                ];
            } else {
                Log::channel('openapi')->warning('[buildRequestBody] ⚠️ Request example is empty or generic', [
                    'example_data' => $exampleData,
                ]);
            }
        } else {
            Log::channel('openapi')->warning('[buildRequestBody] ⚠️ No request_example in documentation', [
                'available_keys' => array_keys($documentation),
            ]);
        }

        // ==========================================
        // GENERIC FALLBACK
        // ==========================================

        Log::channel('openapi')->warning('[buildRequestBody] ❌ Using generic fallback', [
            'controller' => $controller ? class_basename($controller) : 'none',
            'action' => $action,
            'reason' => 'No valid request_example found',
            'form_request' => $documentation['form_request_class'] ?? 'not found',
            'scenario' => $documentation['scenario'] ?? 'not detected',
        ]);

        return $this->getGenericRequestBody();
    }



    /**
     * Get generic request body definition
     */
    protected function getGenericRequestBody(): array
    {
        return [
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => [
                                'type' => 'object',
                                'description' => 'Request payload',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Clear cached OpenAPI specification
     */
    public function clearCache(): void
    {
        $patterns = [
            config('openapi.cache.key_prefix') . '*',
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Generate filename based on filters
     */
    public function generateFilename(string $format, ?array $apiTypes, ?string $environment): string
    {
        $parts = [$format];

        if ($apiTypes) {
            $parts[] = implode('-', $apiTypes);
        } else {
            $parts[] = 'all';
        }

        if ($environment) {
            $parts[] = $environment;
        }

        return implode('-', $parts) . '.' . ($format === 'openapi' ? 'json' : 'json');
    }

    /**
     * Extract action from URI endpoint
     *
     * For routes without names, extracts the action from the last URI segment.
     *
     * Examples:
     * - /users/validate → validate
     * - /users/bulk_create → bulk_create
     * - /users/{id}/restore → restore
     * - /api-apps/{id}/rotate → rotate
     */
    protected function extractActionFromUri(string $uri, string $entity, string $fallbackAction): string
    {
        $parts = explode('/', trim($uri, '/'));
        $nonParams = array_filter($parts, fn($p) => !Str::startsWith($p, '{'));
        $nonParams = array_values($nonParams);

        $lastSegment = end($nonParams);

        if ($lastSegment &&
            $lastSegment !== $entity &&
            $lastSegment !== Str::plural($entity) &&
            $lastSegment !== Str::singular($entity)) {

            Log::channel('openapi')->info('Action extracted from URI endpoint', [
                'uri' => $uri,
                'entity' => $entity,
                'extracted_action' => $lastSegment,
                'fallback_action' => $fallbackAction,
            ]);

            return $lastSegment;
        }

        Log::channel('openapi')->debug('Using fallback action (no custom endpoint)', [
            'uri' => $uri,
            'entity' => $entity,
            'action' => $fallbackAction,
        ]);

        return $fallbackAction;
    }
}