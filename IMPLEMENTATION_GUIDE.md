# Implementation Guide

Deep technical guide for developers working with Laravel OpenAPI Generator.

---

## 📋 Table of Contents

- [Architecture Overview](#architecture-overview)
- [Core Components](#core-components)
- [Metadata Extraction System](#metadata-extraction-system)
- [Documentation Resolution](#documentation-resolution)
- [Variable Detection Strategy](#variable-detection-strategy)
- [Test Generation](#test-generation)
- [Format Generators](#format-generators)
- [Extending the Package](#extending-the-package)
- [Performance Optimization](#performance-optimization)
- [Debugging Guide](#debugging-guide)

---

## 🏗️ Architecture Overview

### System Flow

```
┌─────────────────┐
│ Artisan Command │
│ openapi:generate│
└────────┬────────┘
         │
         ▼
┌─────────────────────────────┐
│   OpenApiServices           │
│   (Main Orchestrator)       │
└────────┬────────────────────┘
         │
         ├──► Route Inspection
         │    └─► Module/Entity Detection
         │    └─► Action Inference
         │
         ├──► Documentation Resolution
         │    └─► Custom Endpoint Lookup
         │    └─► YAML Template Rendering
         │    └─► Automatic Fallback
         │
         ├──► Metadata Extraction
         │    └─► Model Introspection
         │    └─► FormRequest Analysis (4 strategies)
         │    └─► Relationship Detection
         │
         ├──► Request Body Generation
         │    └─► Scenario Detection
         │    └─► Validation Rule Mapping
         │
         ├──► Test Generation
         │    └─► Template Resolution
         │    └─► Snippet Injection
         │
         └──► Format Generation
              ├─► OpenAPI 3.0.3 (JSON/YAML)
              ├─► Postman Collection v2.1
              └─► Insomnia Workspace v4
```

### Directory Structure

```
src/
├── Services/
│   ├── OpenApiServices.php              # Main orchestrator
│   ├── Documentation/
│   │   ├── DocumentationResolver.php    # Resolution logic
│   │   ├── TemplateDocumentationResolver.php
│   │   ├── YamlTemplateRenderer.php
│   │   └── MetadataExtractor.php        # 4-strategy extraction
│   ├── Generators/
│   │   ├── PostmanCollectionGenerator.php
│   │   ├── InsomniaWorkspaceGenerator.php
│   │   └── EnvironmentGenerator.php
│   └── Tests/
│       └── TestTemplateResolver.php
├── Commands/
│   └── GenerateOpenApiCommand.php
├── Providers/
│   └── OpenApiGeneratorServiceProvider.php
├── Facades/
│   └── OpenApi.php
└── Contracts/
    ├── DocumentationGeneratorInterface.php
    └── MetadataExtractorInterface.php
```

---

## 🧩 Core Components

### 1. OpenApiServices

**Responsibility:** Main orchestrator that coordinates all generation activities.

**Key Methods:**

```php
class OpenApiServices
{
    /**
     * Generate complete documentation
     *
     * @param array $apiTypes Filter by API types (api, site, mobile)
     * @param string|null $environment Target environment
     * @param array $formats Formats to generate (openapi, postman, insomnia)
     * @return array Generated file paths
     */
    public function generate(
        array $apiTypes = [],
        ?string $environment = null,
        array $formats = ['openapi', 'postman', 'insomnia']
    ): array;

    /**
     * Parse route into structured data
     *
     * @param \Illuminate\Routing\Route $route
     * @return array [prefix, module, entity, action]
     */
    protected function parseRouteStructure(Route $route): array;

    /**
     * Detect CRUD action from route
     *
     * @param \Illuminate\Routing\Route $route
     * @return string Action name (list, show, create, update, delete, custom)
     */
    protected function detectAction(Route $route): string;

    /**
     * Build OpenAPI operation object
     *
     * @param \Illuminate\Routing\Route $route
     * @param array $documentation
     * @return array OpenAPI operation
     */
    protected function buildOperation(Route $route, array $documentation): array;
}
```

**Critical Logic:**

```php
// Route structure parsing
protected function parseRouteStructure(Route $route): array
{
    $uri = $route->uri();
    $parts = explode('/', $uri);
    
    // Extract components
    $prefix = $parts[0] ?? 'api';  // api, site, mobile
    $module = $this->detectModule($route, $parts);
    $entity = $this->detectEntity($route, $parts);
    $action = $this->detectAction($route);
    
    return compact('prefix', 'module', 'entity', 'action');
}

// Module detection (Nwidart support)
protected function detectModule(Route $route, array $parts): ?string
{
    // Check if route has module middleware
    $middleware = $route->gatherMiddleware();
    foreach ($middleware as $m) {
        if (preg_match('/module:(\w+)/', $m, $matches)) {
            return $matches[1];
        }
    }
    
    // Fallback to URI structure
    if (count($parts) >= 2) {
        return Str::studly($parts[1]);
    }
    
    return null;
}
```

### 2. DocumentationResolver

**Responsibility:** Resolve documentation using 3-level cascade system.

**Resolution Order:**

1. **Custom Endpoints** (highest priority)
2. **YAML Templates**
3. **Automatic Fallback**

**Key Methods:**

```php
class DocumentationResolver
{
    /**
     * Resolve documentation for endpoint
     *
     * @param string $module
     * @param string $entity
     * @param string $action
     * @return array Documentation data
     */
    public function resolve(string $module, string $entity, string $action): array;

    /**
     * Check if custom endpoint exists
     *
     * @param string $key Format: "module.entity.action"
     * @return bool
     */
    protected function hasCustomEndpoint(string $key): bool;

    /**
     * Load custom endpoint documentation
     *
     * @param string $key
     * @return array
     */
    protected function loadCustomEndpoint(string $key): array;

    /**
     * Check if YAML template exists
     *
     * @param string $entity
     * @param string $action
     * @return bool
     */
    protected function hasYamlTemplate(string $entity, string $action): bool;

    /**
     * Load and render YAML template
     *
     * @param string $entity
     * @param string $action
     * @param array $context
     * @return array
     */
    protected function loadYamlTemplate(string $entity, string $action, array $context): array;

    /**
     * Generate fallback documentation
     *
     * @param string $module
     * @param string $entity
     * @param string $action
     * @return array
     */
    protected function generateFallback(string $module, string $entity, string $action): array;
}
```

**Implementation:**

```php
public function resolve(string $module, string $entity, string $action): array
{
    $key = "{$module}.{$entity}.{$action}";
    
    // 1. Check custom endpoints
    if ($this->hasCustomEndpoint($key)) {
        return $this->loadCustomEndpoint($key);
    }
    
    // 2. Check YAML templates
    if ($this->hasYamlTemplate($entity, $action)) {
        $context = $this->buildContext($module, $entity, $action);
        return $this->loadYamlTemplate($entity, $action, $context);
    }
    
    // 3. Fallback to automatic generation
    return $this->generateFallback($module, $entity, $action);
}

protected function buildContext(string $module, string $entity, string $action): array
{
    $entityConfig = config("openapi-docs.entities.{$entity}", []);
    $modelClass = $entityConfig['model'] ?? null;
    
    $fields = [];
    $relations = [];
    
    if ($modelClass && class_exists($modelClass)) {
        $metadata = $this->metadataExtractor->extractFromModel($modelClass);
        $fields = $metadata['fields'] ?? [];
        $relations = $metadata['relations'] ?? [];
    }
    
    return [
        'module' => $module,
        'entity_singular' => $entityConfig['singular'] ?? Str::singular($entity),
        'entity_plural' => $entityConfig['plural'] ?? Str::plural($entity),
        'fields' => $fields,
        'relations' => $relations,
        'action' => $action,
    ];
}
```

---

## 🔬 Metadata Extraction System

### The 4-Strategy Cascade

**Problem:** FormRequests with constructor dependencies fail normal instantiation.

**Example:**
```php
class UpdateProductRequest extends FormRequest
{
    public function __construct(
        protected ProductRepository $repository,
        protected Connection $connection
    ) {}
    
    public function rules()
    {
        return [
            'sku' => [
                'required',
                Rule::unique($this->connection->getDatabaseName() . '.products', 'sku')
                    ->ignore($this->route('id')),
            ],
        ];
    }
}
```

**Solution:** Cascade through 4 strategies until one succeeds.

### Strategy 1: Normal Instantiation

```php
protected function extractViaInstantiation(string $className): ?array
{
    try {
        $request = new $className();
        
        if (method_exists($request, 'rules')) {
            $rules = $request->rules();
            return $this->mapRulesToFields($rules);
        }
        
        return null;
    } catch (\Throwable $e) {
        // Strategy failed, cascade to next
        return null;
    }
}
```

**When it works:** Simple FormRequests without dependencies.

### Strategy 2: Mock Dependency Injection

```php
protected function extractViaMockInjection(string $className): ?array
{
    try {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();
        
        if (!$constructor) {
            return $this->extractViaInstantiation($className);
        }
        
        // Mock all constructor parameters
        $params = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            
            if ($type && !$type->isBuiltin()) {
                $typeName = $type->getName();
                
                // Special handling for known types
                if ($typeName === 'Illuminate\Database\Connection') {
                    $params[] = DB::connection();
                } elseif (interface_exists($typeName) || class_exists($typeName)) {
                    $params[] = Mockery::mock($typeName);
                } else {
                    $params[] = null;
                }
            } else {
                $params[] = null;
            }
        }
        
        $request = $reflection->newInstanceArgs($params);
        
        if (method_exists($request, 'rules')) {
            $rules = $request->rules();
            return $this->mapRulesToFields($rules);
        }
        
        return null;
    } catch (\Throwable $e) {
        Log::debug("Mock injection failed for {$className}: {$e->getMessage()}");
        return null;
    }
}
```

**When it works:** FormRequests with injectable dependencies (repositories, connections).

### Strategy 3: Reflection Without Constructor

```php
protected function extractViaReflection(string $className): ?array
{
    try {
        $reflection = new ReflectionClass($className);
        
        // Create instance WITHOUT calling constructor
        $request = $reflection->newInstanceWithoutConstructor();
        
        // Manually inject required properties
        $this->injectRequiredProperties($request, $reflection);
        
        if (method_exists($request, 'rules')) {
            $rules = $request->rules();
            return $this->mapRulesToFields($rules);
        }
        
        return null;
    } catch (\Throwable $e) {
        Log::debug("Reflection extraction failed for {$className}: {$e->getMessage()}");
        return null;
    }
}

protected function injectRequiredProperties($instance, ReflectionClass $reflection): void
{
    // Inject common Laravel properties
    if ($reflection->hasProperty('connection')) {
        $prop = $reflection->getProperty('connection');
        $prop->setAccessible(true);
        $prop->setValue($instance, DB::connection());
    }
    
    if ($reflection->hasProperty('route')) {
        $prop = $reflection->getProperty('route');
        $prop->setAccessible(true);
        $prop->setValue($instance, Mockery::mock(Route::class));
    }
}
```

**When it works:** When dependencies aren't used in `rules()` method.

### Strategy 4: File Parsing (Last Resort)

```php
protected function extractViaFileParsing(string $className): ?array
{
    try {
        $reflection = new ReflectionClass($className);
        $file = $reflection->getFileName();
        
        if (!$file || !file_exists($file)) {
            return null;
        }
        
        $content = file_get_contents($file);
        
        // Extract rules() method using regex
        $pattern = '/public\s+function\s+rules\(\s*\)\s*{([^}]+)}/s';
        
        if (!preg_match($pattern, $content, $matches)) {
            return null;
        }
        
        $rulesContent = $matches[1];
        
        // Parse return statement
        if (preg_match('/return\s+\[(.*?)\];/s', $rulesContent, $returnMatch)) {
            $rulesArray = $this->parseArrayString($returnMatch[1]);
            return $this->mapRulesToFields($rulesArray);
        }
        
        return null;
    } catch (\Throwable $e) {
        Log::warning("File parsing failed for {$className}: {$e->getMessage()}");
        return null;
    }
}

protected function parseArrayString(string $arrayStr): array
{
    // Convert PHP array syntax to parseable format
    // This is simplified - production version would use PHP parser
    $lines = explode("\n", $arrayStr);
    $rules = [];
    
    foreach ($lines as $line) {
        if (preg_match("/'([^']+)'\s*=>\s*'([^']+)'/", $line, $match)) {
            $rules[$match[1]] = $match[2];
        } elseif (preg_match("/'([^']+)'\s*=>\s*\[(.*?)\]/", $line, $match)) {
            $rules[$match[1]] = $this->parseRuleArray($match[2]);
        }
    }
    
    return $rules;
}
```

**When it works:** When all else fails, but rules are simple arrays.

### Rule-to-Field Mapping

```php
protected function mapRulesToFields(array $rules): array
{
    $fields = [];
    
    foreach ($rules as $field => $ruleSet) {
        $fields[$field] = $this->analyzeRules($field, $ruleSet);
    }
    
    return $fields;
}

protected function analyzeRules(string $field, $ruleSet): array
{
    $rules = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
    
    $fieldData = [
        'name' => $field,
        'type' => $this->detectType($rules),
        'required' => in_array('required', $rules),
        'nullable' => in_array('nullable', $rules),
        'default_value' => $this->getDefaultValue($rules),
        'validation' => $rules,
    ];
    
    // Extract additional constraints
    foreach ($rules as $rule) {
        if (is_string($rule)) {
            // max:255
            if (preg_match('/^max:(\d+)$/', $rule, $match)) {
                $fieldData['max_length'] = (int)$match[1];
            }
            // min:18
            elseif (preg_match('/^min:(\d+)$/', $rule, $match)) {
                $fieldData['min_value'] = (int)$match[1];
            }
            // in:active,inactive
            elseif (preg_match('/^in:(.+)$/', $rule, $match)) {
                $fieldData['enum'] = explode(',', $match[1]);
            }
        }
    }
    
    return $fieldData;
}

protected function detectType(array $rules): string
{
    // Type detection priority
    $typeMap = [
        'boolean' => 'boolean',
        'integer' => 'integer',
        'numeric' => 'number',
        'email' => 'string',
        'url' => 'string',
        'date' => 'string',
        'array' => 'array',
    ];
    
    foreach ($rules as $rule) {
        if (is_string($rule)) {
            $ruleName = explode(':', $rule)[0];
            if (isset($typeMap[$ruleName])) {
                return $typeMap[$ruleName];
            }
        }
    }
    
    return 'string';  // Default
}

protected function getDefaultValue(array $rules): mixed
{
    $type = $this->detectType($rules);
    
    if (in_array('nullable', $rules)) {
        return null;
    }
    
    return match($type) {
        'boolean' => false,
        'integer', 'number' => 0,
        'array' => [],
        default => '',
    };
}
```

---

## 🎯 Variable Detection Strategy

### The Golden Rule

```
IF (action = show|update|delete) 
   AND (parameter = id) 
   AND (exists last_{entity}_id in tracking_variables)
THEN use GLOBAL variable
ELSE use LOCAL variable
```

### Implementation

```php
protected function detectVariableType(Route $route, string $paramName, string $entity, string $action): string
{
    // Get tracking variables from base environment
    $trackingVars = config('openapi.environments.base.tracking_variables', []);
    $expectedGlobalVar = "last_{$entity}_id";
    
    // Check all conditions
    $isStandardCrudAction = in_array($action, ['show', 'update', 'delete']);
    $isIdParameter = $paramName === 'id';
    $hasTrackingVariable = array_key_exists($expectedGlobalVar, $trackingVars);
    
    if ($isStandardCrudAction && $isIdParameter && $hasTrackingVariable) {
        return 'global';  // Use {{ _.last_user_id }}
    }
    
    return 'local';  // Use {{ _.term }} or {{ _.status }}
}

protected function buildVariableName(string $type, string $entity, string $paramName): string
{
    if ($type === 'global') {
        return "last_{$entity}_id";
    }
    
    return $paramName;
}

protected function formatVariableForClient(string $varName, string $client): string
{
    return match($client) {
        'postman' => "{{$varName}}",           // {{last_user_id}}
        'insomnia' => "{{ _.{$varName} }}",    // {{ _.last_user_id }}
        default => $varName,
    };
}
```

### Example Usage

```php
// Route: GET /api/users/{id}
$route = Route::get('/api/users/{id}', [UserController::class, 'show']);
$entity = 'user';
$action = 'show';
$paramName = 'id';

$varType = $this->detectVariableType($route, $paramName, $entity, $action);
// Result: 'global'

$varName = $this->buildVariableName($varType, $entity, $paramName);
// Result: 'last_user_id'

$postmanVar = $this->formatVariableForClient($varName, 'postman');
// Result: '{{last_user_id}}'

$insomniaVar = $this->formatVariableForClient($varName, 'insomnia');
// Result: '{{ _.last_user_id }}'
```

---

## 🧪 Test Generation

### Template Resolution

```php
class TestTemplateResolver
{
    /**
     * Generate tests for endpoint
     *
     * @param string $module
     * @param string $entity
     * @param string $action
     * @return string JavaScript test code
     */
    public function resolve(string $module, string $entity, string $action): string
    {
        $key = "{$module}.{$entity}.{$action}";
        
        // 1. Check custom tests
        $customTests = config("openapi-tests.custom_tests.{$key}");
        if ($customTests) {
            return $this->buildTestCode($customTests);
        }
        
        // 2. Use template for action
        $template = config("openapi-tests.templates.{$action}");
        if ($template) {
            return $this->buildTestCodeFromTemplate($template, $entity);
        }
        
        // 3. Default tests
        return $this->getDefaultTests();
    }
    
    protected function buildTestCodeFromTemplate(array $templateKeys, string $entity): string
    {
        $snippets = config('openapi-tests.snippets', []);
        $testCode = [];
        
        foreach ($templateKeys as $key) {
            if (isset($snippets[$key])) {
                $code = $snippets[$key];
                
                // Replace {entity} placeholder
                $code = str_replace('{entity}', $entity, $code);
                
                $testCode[] = $code;
            }
        }
        
        return implode("\n\n", $testCode);
    }
}
```

### Postman Test Format

```javascript
// Generated for store action
pm.test('Status code is 201', function() {
    pm.response.to.have.status(201);
});

pm.test('Response is JSON', function() {
    pm.response.to.be.json;
});

pm.test('Response has data', function() {
    pm.expect(pm.response.json()).to.have.property('data');
});

// Save ID to global variable
if (pm.response.json().data && pm.response.json().data.id) {
    pm.globals.set('last_user_id', pm.response.json().data.id);
}
```

### Insomnia Test Format

```javascript
// Insomnia uses afterResponseScript
const response = await insomnia.response.json();

// Status check
insomnia.test('Status code is 201', () => {
    insomnia.expect(insomnia.response.code).to.equal(201);
});

// Data check
insomnia.test('Response has data', () => {
    insomnia.expect(response).to.have.property('data');
});

// Save variable
if (response.data && response.data.id) {
    await insomnia.environment.set('last_user_id', response.data.id);
}
```

---

## 📦 Format Generators

### OpenAPI 3.0.3 Generator

```php
class OpenApiGenerator
{
    public function generate(array $operations, array $config): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => $config['info'],
            'servers' => $config['servers'],
            'security' => $this->buildSecurityRequirements($config),
            'paths' => $this->buildPaths($operations),
            'components' => [
                'securitySchemes' => $config['security'],
                'schemas' => $this->buildSchemas($operations),
            ],
            'tags' => $this->buildTags($operations),
        ];
    }
    
    protected function buildPaths(array $operations): array
    {
        $paths = [];
        
        foreach ($operations as $op) {
            $path = $op['path'];
            $method = strtolower($op['method']);
            
            if (!isset($paths[$path])) {
                $paths[$path] = [];
            }
            
            $paths[$path][$method] = [
                'summary' => $op['summary'],
                'description' => $op['description'],
                'tags' => $op['tags'],
                'operationId' => $op['operation_id'],
                'parameters' => $op['parameters'] ?? [],
                'requestBody' => $op['request_body'] ?? null,
                'responses' => $op['responses'],
                'security' => $op['security'] ?? [],
            ];
        }
        
        return $paths;
    }
}
```

### Postman Collection v2.1 Generator

**Critical Structure:**

```json
{
  "info": {
    "_postman_id": "uuid",
    "name": "Collection Name",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Module Name",
      "item": [
        {
          "name": "Entity Name",
          "item": [
            {
              "name": "Request Name",
              "request": {
                "method": "GET",
                "header": [],
                "url": {
                  "raw": "{{base_url}}/api/users/{{last_user_id}}",
                  "host": ["{{base_url}}"],
                  "path": ["api", "users", "{{last_user_id}}"]
                }
              },
              "event": [
                {
                  "listen": "test",
                  "script": {
                    "exec": ["pm.test('Status 200', () => { pm.response.to.have.status(200); });"]
                  }
                }
              ]
            }
          ]
        }
      ]
    }
  ]
}
```

**Generator Implementation:**

```php
class PostmanCollectionGenerator
{
    public function generate(array $operations, array $config): array
    {
        return [
            'info' => [
                '_postman_id' => Str::uuid()->toString(),
                'name' => $config['info']['title'],
                'description' => $config['info']['description'],
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => $this->buildItems($operations),
            'variable' => $this->buildVariables($config),
        ];
    }
    
    protected function buildItems(array $operations): array
    {
        $grouped = $this->groupOperationsByModuleAndEntity($operations);
        $items = [];
        
        foreach ($grouped as $module => $entities) {
            $moduleItem = [
                'name' => $module,
                'item' => [],
            ];
            
            foreach ($entities as $entity => $ops) {
                $entityItem = [
                    'name' => $entity,
                    'item' => $this->buildRequests($ops),
                ];
                
                $moduleItem['item'][] = $entityItem;
            }
            
            $items[] = $moduleItem;
        }
        
        return $items;
    }
    
    protected function buildRequests(array $operations): array
    {
        $requests = [];
        
        foreach ($operations as $op) {
            $request = [
                'name' => $op['name'],
                'request' => [
                    'method' => $op['method'],
                    'header' => $this->buildHeaders($op),
                    'url' => $this->buildUrl($op),
                ],
            ];
            
            if (isset($op['body'])) {
                $request['request']['body'] = $this->buildBody($op['body']);
            }
            
            if (isset($op['tests'])) {
                $request['event'] = [[
                    'listen' => 'test',
                    'script' => [
                        'exec' => explode("\n", $op['tests']),
                    ],
                ]];
            }
            
            $requests[] = $request;
        }
        
        return $requests;
    }
}
```

### Insomnia Workspace v4 Generator

**Critical Requirements:**

1. `_type: "export"` (with underscore)
2. `__export_format: 4` (NOT 5)
3. `scope: "design"` (NOT "collection")
4. Specific resource order

**Correct Structure:**

```json
{
  "_type": "export",
  "__export_format": 4,
  "__export_date": "2025-01-09T12:00:00.000Z",
  "__export_source": "laravel-openapi-generator",
  "resources": [
    {"_type": "workspace", "scope": "design", "_id": "wrk_1"},
    {"_type": "environment", "_id": "env_base"},
    {"_type": "api_spec", "_id": "spc_1"},
    {"_type": "cookie_jar", "_id": "jar_1"},
    {"_type": "request_group", "_id": "fld_1"},
    {"_type": "request", "_id": "req_1"},
    {"_type": "environment", "_id": "env_artisan", "parentId": "env_base"}
  ]
}
```

**Generator Implementation:**

```php
class InsomniaWorkspaceGenerator
{
    protected array $resources = [];
    
    public function generate(array $operations, array $config): array
    {
        $this->resources = [];
        
        // 1. Workspace (first)
        $this->addWorkspace($config);
        
        // 2. Base Environment (second)
        $baseEnvId = $this->addBaseEnvironment($config);
        
        // 3. API Spec (third)
        $this->addApiSpec($config);
        
        // 4. Cookie Jar (fourth)
        $this->addCookieJar();
        
        // 5. Folders & Requests (fifth)
        $this->addRequestsAndFolders($operations);
        
        // 6. Sub-environments (last)
        $this->addSubEnvironments($config, $baseEnvId);
        
        return [
            '_type' => 'export',
            '__export_format' => 4,
            '__export_date' => now()->toIso8601String(),
            '__export_source' => 'laravel-openapi-generator',
            'resources' => $this->resources,
        ];
    }
    
    protected function addWorkspace(array $config): void
    {
        $this->resources[] = [
            '_type' => 'workspace',
            '_id' => 'wrk_' . $this->generateId(),
            'name' => $config['info']['title'],
            'description' => $config['info']['description'],
            'scope' => 'design',  // CRITICAL: not "collection"
        ];
    }
    
    protected function addBaseEnvironment(array $config): string
    {
        $id = 'env_base_' . $this->generateId();
        $envConfig = $config['environments']['base'];
        
        $data = array_merge(
            $envConfig['variables'] ?? [],
            $envConfig['tracking_variables'] ?? []
        );
        
        $this->resources[] = [
            '_type' => 'environment',
            '_id' => $id,
            'name' => 'Base Environment',
            'data' => $data,
        ];
        
        return $id;
    }
    
    protected function addRequest(array $operation, string $folderId): void
    {
        $this->resources[] = [
            '_type' => 'request',
            '_id' => 'req_' . $this->generateId(),
            'parentId' => $folderId,
            'name' => $operation['name'],
            'method' => $operation['method'],
            'url' => $this->formatUrl($operation['url']),
            'headers' => $this->buildHeaders($operation),
            'body' => $operation['body'] ?? [],
            'authentication' => $this->buildAuthentication($operation),
            'parameters' => $operation['parameters'] ?? [],
        ];
    }
}
```

---

## 🔧 Extending the Package

### Adding Custom Documentation Generator

```php
namespace App\Generators;

use Ronu\OpenApiGenerator\Contracts\DocumentationGeneratorInterface;

class SwaggerUiGenerator implements DocumentationGeneratorInterface
{
    public function generate(array $operations, array $config): string
    {
        // Generate Swagger UI HTML
        $spec = json_encode($this->buildOpenApiSpec($operations, $config));
        
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist/swagger-ui.css" />
        </head>
        <body>
            <div id="swagger-ui"></div>
            <script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>
            <script>
                SwaggerUIBundle({
                    spec: {$spec},
                    dom_id: '#swagger-ui'
                });
            </script>
        </body>
        </html>
        HTML;
    }
}
```

**Register in Service Provider:**

```php
$this->app->bind('openapi.generator.swagger', SwaggerUiGenerator::class);
```

### Adding Custom Metadata Extractor

```php
namespace App\Extractors;

class CustomMetadataExtractor extends MetadataExtractor
{
    protected function extractCustomData(string $className): array
    {
        // Add custom logic
        $baseData = parent::extractFromClass($className);
        
        // Enhance with custom metadata
        $customData = $this->extractFromDocBlocks($className);
        
        return array_merge($baseData, $customData);
    }
    
    protected function extractFromDocBlocks(string $className): array
    {
        $reflection = new ReflectionClass($className);
        $docComment = $reflection->getDocComment();
        
        if (!$docComment) {
            return [];
        }
        
        // Parse custom tags
        preg_match_all('/@api-(\w+)\s+(.+)/', $docComment, $matches);
        
        $metadata = [];
        foreach ($matches[1] as $i => $tag) {
            $metadata[$tag] = trim($matches[2][$i]);
        }
        
        return $metadata;
    }
}
```

### Adding Custom Test Template

```php
// config/openapi-tests.php
'templates' => [
    'custom_action' => [
        'status_200',
        'custom_validation',
    ],
],

'snippets' => [
    'custom_validation' => <<<JS
    pm.test('Custom validation', function() {
        const response = pm.response.json();
        pm.expect(response).to.have.property('custom_field');
        pm.expect(response.custom_field).to.be.a('string');
    });
    JS,
],
```

---

## ⚡ Performance Optimization

### 1. Metadata Caching

```php
use Illuminate\Support\Facades\Cache;

protected function getCachedMetadata(string $className): array
{
    $cacheKey = "openapi.metadata.{$className}";
    
    return Cache::remember($cacheKey, 3600, function() use ($className) {
        return $this->metadataExtractor->extract($className);
    });
}

// Clear cache when needed
public function clearCache(): void
{
    Cache::tags(['openapi'])->flush();
}
```

### 2. Lazy Loading

```php
protected function loadOperationsLazily(array $routes): \Generator
{
    foreach ($routes as $route) {
        yield $this->buildOperation($route);
    }
}

// Use in generator
foreach ($this->loadOperationsLazily($routes) as $operation) {
    $this->addToCollection($operation);
}
```

### 3. Parallel Processing

```php
use Illuminate\Support\Facades\Parallel;

public function generateAll(array $apiTypes): array
{
    return Parallel::run([
        fn() => $this->generateOpenApi($apiTypes),
        fn() => $this->generatePostman($apiTypes),
        fn() => $this->generateInsomnia($apiTypes),
    ]);
}
```

---

## 🐛 Debugging Guide

### Enable Debug Mode

```php
// config/openapi.php
'debug' => env('OPENAPI_DEBUG', false),
```

### Add Logging

```php
use Illuminate\Support\Facades\Log;

// In OpenApiServices
Log::channel('openapi')->info('Generating documentation', [
    'api_types' => $apiTypes,
    'routes_count' => count($routes),
]);

// In MetadataExtractor
Log::channel('openapi')->debug("Extracting metadata from {$className}", [
    'strategy' => $strategyName,
    'success' => $success,
]);
```

### Create Custom Log Channel

```php
// config/logging.php
'channels' => [
    'openapi' => [
        'driver' => 'daily',
        'path' => storage_path('logs/openapi.log'),
        'level' => 'debug',
        'days' => 14,
    ],
],
```

### Debug Command Output

```bash
php artisan openapi:generate --all -vvv
```

### Validate Generated Files

```php
// Use JSON schema validation
$validator = new JsonSchema\Validator();
$spec = json_decode(file_get_contents('openapi.json'));
$schema = json_decode(file_get_contents('openapi-3.0.3-schema.json'));

$validator->validate($spec, $schema);

if ($validator->isValid()) {
    echo "Valid OpenAPI spec\n";
} else {
    foreach ($validator->getErrors() as $error) {
        echo sprintf("[%s] %s\n", $error['property'], $error['message']);
    }
}
```

---

## 📚 Additional Resources

- [OpenAPI 3.0.3 Specification](https://spec.openapis.org/oas/v3.0.3)
- [Postman Collection Format v2.1](https://schema.postman.com/json/collection/v2.1.0/)
- [Insomnia Documentation](https://docs.insomnia.rest/)
- [Laravel Reflection](https://www.php.net/manual/en/book.reflection.php)

---

**Happy implementing! 🚀**
