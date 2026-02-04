# Implementation Guide

**Advanced patterns, architecture deep-dive, and extension guide for Laravel OpenAPI Generator**

This guide is for senior developers who want to understand how the package works internally and how to extend it for custom requirements.

---

## 📋 Table of Contents

- [Architecture Overview](#architecture-overview)
- [Service Layer Deep Dive](#service-layer-deep-dive)
- [Metadata Extraction System](#metadata-extraction-system)
- [Template System](#template-system)
- [Documentation Resolution](#documentation-resolution)
- [Test Generation](#test-generation)
- [Environment Management](#environment-management)
- [Extension Patterns](#extension-patterns)
- [Performance Optimization](#performance-optimization)
- [Custom Generators](#custom-generators)
- [Troubleshooting Patterns](#troubleshooting-patterns)

---

## 🏗️ Architecture Overview

### High-Level Flow

```
User Command
    ↓
OpenApiServices (Orchestrator)
    ↓
├─→ Route Inspector (analyzes all routes)
├─→ DocumentationResolver (resolves docs for each endpoint)
│   ├─→ Custom Endpoint Lookup
│   ├─→ Template Renderer
│   └─→ Fallback Generator
├─→ MetadataExtractor (extracts from FormRequests & Models)
│   ├─→ Strategy 1: Normal Instantiation
│   ├─→ Strategy 2: Mock Dependencies
│   ├─→ Strategy 3: Reflection
│   └─→ Strategy 4: File Parsing
├─→ TestTemplateResolver (generates test scripts)
└─→ Output Generators
    ├─→ OpenAPI Generator (JSON/YAML)
    ├─→ PostmanCollectionGenerator
    └─→ InsomniaWorkspaceGenerator
```

### Core Services

```
src/
├── Services/
│   ├── OpenApiServices.php              # Main orchestrator
│   ├── DocumentationResolver.php        # Documentation resolution
│   ├── PostmanCollectionGenerator.php   # Postman export
│   ├── InsomniaWorkspaceGenerator.php   # Insomnia export
│   ├── EnvironmentGenerator.php         # Environment management
│   ├── TestTemplateResolver.php         # Test generation
│   └── Documentation/
│       ├── TemplateDocumentationResolver.php
│       ├── MetadataExtractor.php
│       └── YamlTemplateRenderer.php
├── Controllers/
│   └── OpenApiController.php            # HTTP endpoints
├── Commands/
│   └── GenerateOpenApiSpec.php          # Artisan command
└── Helpers/
    └── PlaceholderHelper.php             # Variable replacement
```

---

## 🔍 Service Layer Deep Dive

### OpenApiServices - The Orchestrator

The main service that coordinates all documentation generation.

#### Key Responsibilities

1. **Route Inspection**
```php
protected function inspectRoutes(): void
{
    $routes = Route::getRoutes();
    
    foreach ($routes as $route) {
        // Filter API routes only
        if (!$this->isApiRoute($route)) {
            continue;
        }
        
        // Apply API type filter if set
        if (!$this->matchesApiTypeFilter($route->uri())) {
            continue;
        }
        
        // Process the route
        $this->processRoute($route);
    }
}
```

2. **Structure Detection**
```php
protected function parseUriStructure(string $uri): array
{
    // Example: api/security/users/{id}
    // Result: ['api', 'security', 'users', '{id}']
    $parts = explode('/', trim($uri, '/'));
    
    return [
        'prefix' => $parts[0] ?? '',           // 'api'
        'module' => $parts[1] ?? 'general',    // 'security'
        'entity' => $parts[2] ?? 'resource',   // 'users'
        'params' => array_slice($parts, 3),    // ['{id}']
    ];
}
```

3. **Action Detection**
```php
protected function detectAction(string $method, string $uri, ?string $actionName): string
{
    // Priority 1: Explicit action from controller method
    if ($actionName && in_array($actionName, ['index', 'show', 'store', 'update', 'destroy'])) {
        return $actionName;
    }
    
    // Priority 2: From URI pattern
    if ($method === 'GET' && !str_contains($uri, '{')) {
        return 'list';
    }
    
    if ($method === 'GET' && str_contains($uri, '{')) {
        return 'show';
    }
    
    if ($method === 'POST') {
        return 'create';
    }
    
    if (in_array($method, ['PUT', 'PATCH'])) {
        return 'update';
    }
    
    if ($method === 'DELETE') {
        return 'delete';
    }
    
    // Priority 3: Custom from URI
    $parts = explode('/', $uri);
    $lastPart = end($parts);
    
    return !str_contains($lastPart, '{') ? $lastPart : 'custom';
}
```

#### API Type Filtering

```php
public function setApiTypeFilter(array $types): self
{
    $this->apiTypeFilter = $types;
    
    Log::channel('openapi')->info('API type filter set', [
        'types' => $types,
        'available_types' => array_keys(config('openapi.api_types')),
    ]);
    
    return $this;
}

protected function matchesApiTypeFilter(string $uri): bool
{
    if (empty($this->apiTypeFilter)) {
        return true;
    }
    
    $apiTypes = config('openapi.api_types');
    
    foreach ($this->apiTypeFilter as $filterType) {
        $prefix = $apiTypes[$filterType]['prefix'] ?? '';
        
        if (Str::startsWith($uri, $prefix . '/') || $uri === $prefix) {
            return true;
        }
    }
    
    return false;
}
```

---

## 🔬 Metadata Extraction System

### The 4-Strategy Cascade

The most critical component for extracting validation rules from FormRequests.

#### Problem Statement

Laravel FormRequests can have constructor dependencies:

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

**Challenge**: Can't instantiate without providing dependencies.

#### Solution: 4-Strategy Cascade

### Strategy 1: Normal Instantiation

**When it works**: Simple FormRequests without dependencies.

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
        Log::channel('openapi')->debug('Strategy 1 failed', [
            'class' => $className,
            'error' => $e->getMessage(),
        ]);
        return null;
    }
}
```

### Strategy 2: Mock Dependencies

**When it works**: Complex FormRequests with injected dependencies.

```php
protected function extractViaMocking(string $className): ?array
{
    try {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();
        
        if (!$constructor) {
            return null;
        }
        
        // Build mock dependencies
        $dependencies = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            
            if (!$type || $type->isBuiltin()) {
                $dependencies[] = null;
                continue;
            }
            
            $typeName = $type->getName();
            
            // Create mock for the dependency
            if (class_exists($typeName) || interface_exists($typeName)) {
                $dependencies[] = Mockery::mock($typeName);
            } else {
                $dependencies[] = null;
            }
        }
        
        // Instantiate with mocked dependencies
        $request = $reflection->newInstanceArgs($dependencies);
        
        if (method_exists($request, 'rules')) {
            $rules = $request->rules();
            return $this->mapRulesToFields($rules);
        }
        
        return null;
        
    } catch (\Throwable $e) {
        Log::channel('openapi')->debug('Strategy 2 failed', [
            'class' => $className,
            'error' => $e->getMessage(),
        ]);
        return null;
    } finally {
        Mockery::close();
    }
}
```

### Strategy 3: Reflection Without Constructor

**When it works**: Can analyze rules without invoking constructor.

```php
protected function extractViaReflection(string $className): ?array
{
    try {
        $reflection = new ReflectionClass($className);
        $method = $reflection->getMethod('rules');
        
        // Use reflection to get the code
        $file = file_get_contents($reflection->getFileName());
        
        // Parse the rules() method
        $start = $method->getStartLine() - 1;
        $end = $method->getEndLine();
        $length = $end - $start;
        
        $lines = array_slice(
            explode("\n", $file),
            $start,
            $length
        );
        
        $code = implode("\n", $lines);
        
        // Extract return array
        if (preg_match('/return\s+\[(.*?)\];/s', $code, $matches)) {
            $rulesCode = $matches[1];
            
            // Parse rules array
            return $this->parseRulesCode($rulesCode);
        }
        
        return null;
        
    } catch (\Throwable $e) {
        Log::channel('openapi')->debug('Strategy 3 failed', [
            'class' => $className,
            'error' => $e->getMessage(),
        ]);
        return null;
    }
}
```

### Strategy 4: File Parsing

**When it works**: Last resort, parses file as text.

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
        
        // Find rules() method
        if (preg_match(
            '/public\s+function\s+rules\s*\(\s*\)\s*{(.*?)}/s',
            $content,
            $matches
        )) {
            $methodBody = $matches[1];
            
            // Extract simple rules
            $fields = [];
            
            // Match patterns like 'field' => 'required|string'
            if (preg_match_all(
                "/'(\w+)'\s*=>\s*'([^']+)'/",
                $methodBody,
                $fieldMatches,
                PREG_SET_ORDER
            )) {
                foreach ($fieldMatches as $match) {
                    $field = $match[1];
                    $rules = $match[2];
                    
                    $fields[$field] = $this->parseRuleString($rules);
                }
            }
            
            return $fields;
        }
        
        return null;
        
    } catch (\Throwable $e) {
        Log::channel('openapi')->debug('Strategy 4 failed', [
            'class' => $className,
            'error' => $e->getMessage(),
        ]);
        return null;
    }
}
```

### Cascade Execution

```php
public function extractFromFormRequest(string $className): array
{
    Log::channel('openapi')->info('Starting extraction cascade', [
        'class' => $className,
    ]);
    
    // Try strategies in order
    $strategies = [
        'instantiation' => fn() => $this->extractViaInstantiation($className),
        'mocking' => fn() => $this->extractViaMocking($className),
        'reflection' => fn() => $this->extractViaReflection($className),
        'parsing' => fn() => $this->extractViaFileParsing($className),
    ];
    
    foreach ($strategies as $name => $strategy) {
        $result = $strategy();
        
        if ($result !== null) {
            Log::channel('openapi')->info('Extraction successful', [
                'class' => $className,
                'strategy' => $name,
                'fields' => count($result),
            ]);
            
            return $result;
        }
    }
    
    Log::channel('openapi')->warning('All extraction strategies failed', [
        'class' => $className,
    ]);
    
    return [];
}
```

---

## 📄 Template System

### Template Structure

Templates use JSON with Mustache-style variables:

```json
{
  "summary": "List all {entity_plural}",
  "description": "Retrieve a paginated list of {entity_plural} with optional filtering and sorting",
  "tags": ["{module}", "{entity_plural}"],
  "parameters": [
    {
      "name": "page",
      "in": "query",
      "description": "Page number for pagination",
      "schema": {
        "type": "integer",
        "default": 1
      }
    },
    {
      "name": "per_page",
      "in": "query",
      "description": "Items per page",
      "schema": {
        "type": "integer",
        "default": 15,
        "maximum": 100
      }
    },
    {
      "name": "sort",
      "in": "query",
      "description": "Sort field",
      "schema": {
        "type": "string",
        "enum": ["{fields}"]
      }
    }
  ],
  "responses": {
    "200": {
      "description": "Successful response",
      "content": {
        "application/json": {
          "schema": {
            "type": "object",
            "properties": {
              "data": {
                "type": "array",
                "items": {
                  "$ref": "#/components/schemas/{entity_singular_pascal}"
                }
              },
              "meta": {
                "$ref": "#/components/schemas/PaginationMeta"
              }
            }
          }
        }
      }
    }
  }
}
```

### Template Variables

| Variable | Example Value | Description |
|----------|---------------|-------------|
| `{module}` | `Security` | Module name |
| `{entity_singular}` | `user` | Entity singular lowercase |
| `{entity_plural}` | `users` | Entity plural lowercase |
| `{entity_singular_pascal}` | `User` | Entity singular PascalCase |
| `{entity_plural_pascal}` | `Users` | Entity plural PascalCase |
| `{fields}` | `["name", "email", "status"]` | Array of field names |
| `{relations}` | `["posts", "roles"]` | Array of relation names |

### Template Rendering

```php
class ValidJSONTemplateProcessor
{
    protected bool $debug;
    
    public function process(string $templatePath, array $variables): array
    {
        // Read template
        $template = file_get_contents($templatePath);
        
        if ($this->debug) {
            Log::channel('openapi')->debug('Processing template', [
                'template' => $templatePath,
                'variables' => array_keys($variables),
            ]);
        }
        
        // Replace variables
        foreach ($variables as $key => $value) {
            $placeholder = "{{$key}}";
            
            if (is_array($value)) {
                $value = json_encode($value);
            }
            
            $template = str_replace($placeholder, $value, $template);
        }
        
        // Parse as JSON
        $parsed = json_decode($template, true);
        
        if ($parsed === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                'Invalid JSON after variable replacement: ' . json_last_error_msg()
            );
        }
        
        return $parsed;
    }
}
```

### Custom Template Example

Create `resources/openapi/templates/custom/auth.login.json`:

```json
{
  "summary": "User Authentication",
  "description": "Authenticate user credentials and return access token with user profile",
  "tags": ["auth", "authentication"],
  "requestBody": {
    "required": true,
    "content": {
      "application/json": {
        "schema": {
          "type": "object",
          "required": ["email", "password"],
          "properties": {
            "email": {
              "type": "string",
              "format": "email",
              "example": "admin@example.com"
            },
            "password": {
              "type": "string",
              "format": "password",
              "minLength": 8,
              "example": "SecurePassword123!"
            },
            "remember": {
              "type": "boolean",
              "default": false,
              "description": "Remember this device for 30 days"
            }
          }
        }
      }
    }
  },
  "responses": {
    "200": {
      "description": "Authentication successful",
      "content": {
        "application/json": {
          "schema": {
            "type": "object",
            "properties": {
              "token": {
                "type": "string",
                "description": "JWT access token"
              },
              "token_type": {
                "type": "string",
                "enum": ["Bearer"]
              },
              "expires_in": {
                "type": "integer",
                "description": "Token validity in seconds"
              },
              "user": {
                "$ref": "#/components/schemas/User"
              }
            }
          },
          "example": {
            "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
            "token_type": "Bearer",
            "expires_in": 3600,
            "user": {
              "id": 1,
              "name": "Admin User",
              "email": "admin@example.com"
            }
          }
        }
      }
    },
    "401": {
      "description": "Authentication failed",
      "content": {
        "application/json": {
          "schema": {
            "$ref": "#/components/schemas/Error"
          },
          "example": {
            "message": "Invalid credentials",
            "errors": {
              "email": ["These credentials do not match our records."]
            }
          }
        }
      }
    }
  },
  "security": []
}
```

---

## 📖 Documentation Resolution

### Resolution Priority

```
1. Custom Endpoints (config/openapi-docs.php)
   ↓
2. Custom Templates (resources/openapi/templates/custom/{entity}.{action}.json)
   ↓
3. Generic Templates (resources/openapi/templates/generic/{action}.json)
   ↓
4. Package Templates (vendor/.../resources/templates/generic/{action}.json)
   ↓
5. Fallback Generation (basic spec from route data)
```

### DocumentationResolver Implementation

```php
class DocumentationResolver
{
    protected TemplateDocumentationResolver $templateResolver;
    protected MetadataExtractor $metadataExtractor;
    
    public function resolve(
        string $module,
        string $entity,
        string $action,
        array $routeData
    ): array {
        // Step 1: Check custom endpoints
        $customKey = "{$entity}.{$action}";
        
        if ($custom = config("openapi-docs.custom_endpoints.{$customKey}")) {
            Log::channel('openapi')->info('Using custom endpoint documentation', [
                'key' => $customKey,
            ]);
            
            return $this->enhanceCustomEndpoint($custom, $routeData);
        }
        
        // Step 2: Try template resolution
        $templateDoc = $this->templateResolver->resolve($module, $entity, $action);
        
        if ($templateDoc !== null) {
            Log::channel('openapi')->info('Using template documentation', [
                'module' => $module,
                'entity' => $entity,
                'action' => $action,
            ]);
            
            return $this->enhanceTemplateDoc($templateDoc, $routeData);
        }
        
        // Step 3: Fallback to automatic generation
        Log::channel('openapi')->info('Using fallback documentation', [
            'module' => $module,
            'entity' => $entity,
            'action' => $action,
        ]);
        
        return $this->generateFallbackDoc($module, $entity, $action, $routeData);
    }
    
    protected function enhanceCustomEndpoint(array $custom, array $routeData): array
    {
        // Merge custom documentation with extracted metadata
        $formRequest = $routeData['form_request'] ?? null;
        
        if ($formRequest) {
            $fields = $this->metadataExtractor->extractFromFormRequest($formRequest);
            $custom['request_body'] = $this->buildRequestBody($fields);
        }
        
        return $custom;
    }
    
    protected function enhanceTemplateDoc(array $template, array $routeData): array
    {
        // Enhance template with route-specific data
        if (isset($routeData['parameters'])) {
            $template['parameters'] = array_merge(
                $template['parameters'] ?? [],
                $routeData['parameters']
            );
        }
        
        if (isset($routeData['security'])) {
            $template['security'] = $routeData['security'];
        }
        
        return $template;
    }
    
    protected function generateFallbackDoc(
        string $module,
        string $entity,
        string $action,
        array $routeData
    ): array {
        $summary = $this->generateSummary($entity, $action);
        $description = $this->generateDescription($entity, $action);
        
        return [
            'summary' => $summary,
            'description' => $description,
            'tags' => [$module, $entity],
            'parameters' => $routeData['parameters'] ?? [],
            'responses' => $this->generateBasicResponses($action),
        ];
    }
}
```

---

## 🧪 Test Generation

### Template-Based Test Generation

Tests are generated from templates defined in `config/openapi-tests.php`:

```php
public function generateTestScript(
    string $action,
    string $entity,
    string $format = 'postman'
): array {
    // Get template for action
    $template = config("openapi-tests.templates.{$action}", []);
    
    if (empty($template)) {
        return [];
    }
    
    $script = [];
    
    // Build test script from checks
    foreach ($template as $check) {
        $snippet = config("openapi-tests.snippets.{$check}.{$format}");
        
        if ($snippet) {
            // Replace entity placeholder
            $snippet = str_replace('{entity}', Str::snake($entity), $snippet);
            
            $script[] = $snippet;
        }
    }
    
    // Add custom tests
    $customKey = "{$entity}.{$action}";
    if ($custom = config("openapi-tests.custom_tests.{$customKey}")) {
        $script = array_merge($script, $custom);
    }
    
    return $script;
}
```

### Custom Test Example

```php
// config/openapi-tests.php
'custom_tests' => [
    'products.import' => [
        "pm.test('Response has import_id', function() {
            pm.expect(pm.response.json()).to.have.property('import_id');
        });",
        "pm.test('Imported count is positive', function() {
            pm.expect(pm.response.json().imported).to.be.above(0);
        });",
        "pm.globals.set('last_import_id', pm.response.json().import_id);",
    ],
],
```

---

## 🌍 Environment Management

### Hierarchical Environment Structure

```
Base Environment (contains tracking_variables)
  ├─ base_url: (no value, overridden by children)
  ├─ token: ""
  ├─ tracking_variables:
  │   ├─ last_user_id: ""
  │   ├─ last_product_id: ""
  │   └─ last_order_id: ""
  │
  ├─→ Artisan Environment
  │    ├─ base_url: "http://127.0.0.1:8000"
  │    └─ (inherits tracking_variables)
  │
  ├─→ Local Environment
  │    ├─ base_url: "http://localhost/project"
  │    └─ (inherits tracking_variables)
  │
  └─→ Production Environment
       ├─ base_url: "https://api.production.com"
       └─ (inherits tracking_variables)
```

### Environment Generation

```php
class EnvironmentGenerator
{
    public function generate(array $environments): array
    {
        $generated = [];
        
        // Base environment (with tracking variables)
        $base = $this->generateBase($environments['base']);
        $generated['base'] = $base;
        
        // Sub-environments (inherit from base)
        foreach ($environments as $name => $config) {
            if ($name === 'base') {
                continue;
            }
            
            $generated[$name] = $this->generateSubEnvironment($name, $config, $base);
        }
        
        return $generated;
    }
    
    protected function generateBase(array $config): array
    {
        return [
            'name' => 'Base Environment',
            'variables' => array_merge(
                [
                    'base_url' => '',
                    'token' => '',
                    'api_key' => '',
                ],
                $config['tracking_variables'] ?? []
            ),
        ];
    }
    
    protected function generateSubEnvironment(
        string $name,
        array $config,
        array $base
    ): array {
        // Inherit all variables from base
        $variables = $base['variables'];
        
        // Override with sub-environment specifics
        $variables['base_url'] = $config['url'];
        
        return [
            'name' => ucfirst($name) . ' Environment',
            'variables' => $variables,
            'inherits_from' => 'base',
        ];
    }
}
```

---

## 🔌 Extension Patterns

### Adding a Custom Generator

```php
namespace App\Generators;

use Ronu\OpenApiGenerator\Contracts\GeneratorInterface;

class SwaggerUiGenerator implements GeneratorInterface
{
    public function generate(array $spec, array $config): string
    {
        $specJson = json_encode($spec, JSON_PRETTY_PRINT);
        
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>{$spec['info']['title']}</title>
            <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist/swagger-ui.css" />
        </head>
        <body>
            <div id="swagger-ui"></div>
            <script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>
            <script>
                window.onload = function() {
                    SwaggerUIBundle({
                        spec: {$specJson},
                        dom_id: '#swagger-ui',
                        deepLinking: true,
                        presets: [
                            SwaggerUIBundle.presets.apis,
                            SwaggerUIBundle.SwaggerUIStandalonePreset
                        ],
                    });
                };
            </script>
        </body>
        </html>
        HTML;
    }
}
```

Register in a service provider:

```php
$this->app->bind('openapi.generator.swagger_ui', SwaggerUiGenerator::class);
```

### Custom Metadata Extractor

Extend the base extractor:

```php
namespace App\Extractors;

use Ronu\OpenApiGenerator\Services\Documentation\MetadataExtractor;

class EnhancedMetadataExtractor extends MetadataExtractor
{
    public function extractFromFormRequest(string $className): array
    {
        // Call parent implementation first
        $fields = parent::extractFromFormRequest($className);
        
        // Add custom enhancements
        $fields = $this->addApiDocAnnotations($className, $fields);
        $fields = $this->enrichFromDatabase($className, $fields);
        
        return $fields;
    }
    
    protected function addApiDocAnnotations(string $className, array $fields): array
    {
        $reflection = new \ReflectionClass($className);
        $docComment = $reflection->getDocComment();
        
        if (!$docComment) {
            return $fields;
        }
        
        // Parse custom @api-field annotations
        preg_match_all(
            '/@api-field\s+(\w+)\s+"([^"]+)"/',
            $docComment,
            $matches,
            PREG_SET_ORDER
        );
        
        foreach ($matches as $match) {
            $fieldName = $match[1];
            $description = $match[2];
            
            if (isset($fields[$fieldName])) {
                $fields[$fieldName]['description'] = $description;
            }
        }
        
        return $fields;
    }
    
    protected function enrichFromDatabase(string $className, array $fields): array
    {
        // Get model class from request
        $modelClass = $this->getModelFromRequest($className);
        
        if (!$modelClass || !class_exists($modelClass)) {
            return $fields;
        }
        
        // Get database column information
        $model = new $modelClass();
        $table = $model->getTable();
        $columns = \DB::select("DESCRIBE {$table}");
        
        foreach ($columns as $column) {
            $fieldName = $column->Field;
            
            if (isset($fields[$fieldName])) {
                // Enrich with database constraints
                if ($column->Null === 'NO') {
                    $fields[$fieldName]['required'] = true;
                }
                
                if ($column->Default !== null) {
                    $fields[$fieldName]['default'] = $column->Default;
                }
            }
        }
        
        return $fields;
    }
}
```

Bind in service provider:

```php
$this->app->bind(
    \Ronu\OpenApiGenerator\Services\Documentation\MetadataExtractor::class,
    \App\Extractors\EnhancedMetadataExtractor::class
);
```

---

## ⚡ Performance Optimization

### 1. Enable Caching

```php
// config/openapi.php
'cache' => [
    'enabled' => true,
    'ttl' => 3600, // 1 hour
    'key' => 'openapi_spec',
],
```

### 2. Selective Route Processing

```php
// Only process changed routes
public function generate(bool $useCache = true): array
{
    if ($useCache && Cache::has($this->getCacheKey())) {
        return Cache::get($this->getCacheKey());
    }
    
    $spec = $this->buildSpec();
    
    Cache::put($this->getCacheKey(), $spec, $this->cacheTtl);
    
    return $spec;
}
```

### 3. Lazy Loading

```php
// Load templates only when needed
protected function getTemplate(string $name): array
{
    if (!isset($this->templateCache[$name])) {
        $this->templateCache[$name] = $this->loadTemplate($name);
    }
    
    return $this->templateCache[$name];
}
```

### 4. Batch Processing

```php
// Process routes in batches
protected function processRoutesBatch(array $routes, int $batchSize = 50): void
{
    $batches = array_chunk($routes, $batchSize);
    
    foreach ($batches as $batch) {
        foreach ($batch as $route) {
            $this->processRoute($route);
        }
        
        // Yield to prevent memory exhaustion
        gc_collect_cycles();
    }
}
```

---

## 🔧 Troubleshooting Patterns

### Enable Debug Logging

```php
// config/openapi.php
'debug' => true,

// config/logging.php
'channels' => [
    'openapi' => [
        'driver' => 'single',
        'path' => storage_path('logs/openapi.log'),
        'level' => 'debug',
    ],
],
```

### Inspect Generated Spec

```php
php artisan tinker

>>> $service = app(\Ronu\OpenApiGenerator\Services\OpenApiServices::class);
>>> $spec = $service->generate();
>>> dump($spec['paths']);
```

### Validate Against Schema

```php
use cebe\openapi\Reader;
use cebe\openapi\exceptions\IOException;

$spec = file_get_contents(storage_path('app/openapi.json'));
$openapi = Reader::readFromJson($spec);

if ($openapi->validate()) {
    echo "Valid OpenAPI spec\n";
} else {
    print_r($openapi->getErrors());
}
```

### Common Issues and Solutions

#### Issue: "Maximum function nesting level reached"

**Cause**: Circular dependencies in route resolution.

**Solution**: Increase Xdebug nesting level or disable:
```bash
# php.ini
xdebug.max_nesting_level = 512
```

#### Issue: "Memory limit exhausted"

**Cause**: Too many routes or large models.

**Solution**: Increase memory:
```bash
php -d memory_limit=512M artisan openapi:generate
```

Or batch processing:
```php
// Process routes in smaller batches
'batch_size' => 25,
```

---

## 📚 Additional Resources

- **Laravel Documentation**: https://laravel.com/docs
- **OpenAPI Specification**: https://swagger.io/specification/
- **Postman Collection Format**: https://schema.postman.com/
- **Insomnia Workspace Format**: https://docs.insomnia.rest/

---

## 🎯 Best Practices

1. **Keep FormRequests Clean**: Simple rules extract better
2. **Use Templates**: Custom templates for complex endpoints
3. **Document Edge Cases**: Add to `custom_endpoints`
4. **Test Regularly**: Import generated docs into tools
5. **Enable Caching**: In production environments
6. **Version Control**: Commit generated specs for comparison

---

**Ready to extend the package? Start with [Custom Generators](#custom-generators) and [Extension Patterns](#extension-patterns)!**