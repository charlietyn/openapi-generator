<?php

namespace Ronu\OpenApiGenerator\Services\Documentation;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

/**
 * Metadata Extractor - ENHANCED VERSION v2 WITH $this->connection FIX
 *
 * CRITICAL FIX: Handles Rule classes that use $this->connection or other instance properties
 *
 * @package App\Services\Documentation
 * @version 2.2.1
 */
class MetadataExtractor
{
    protected array $cache = [];

    /**
     * Extract complete metadata for entity operation WITH SCENARIO SUPPORT
     *
     * @param string $entity Entity name
     * @param string $module Module name
     * @param string $action Action name
     * @param mixed $route Route data (for middleware inspection)
     * @return array Complete metadata payload
     */
    public function extractForEntity(
        string      $entity,
        string      $module,
        string|null $controller,
        string      $action = 'list',
        mixed       $route = null
    ): array
    {
        $cacheKey = "{$module}.{$entity}.{$action}";
        $this->cache['current_entity'] = $entity;
        $this->cache['current_module'] = $module;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        Log::channel('openapi')->debug('Extracting metadata with FormRequest support', [
            'entity' => $entity,
            'module' => $module,
            'action' => $action,
        ]);

        $modelClass = $this->findModelClass($entity, $module);

        $formRequestClass = $this->findFormRequest($entity, $module, $action);

        if (!$formRequestClass && $controller) {
            $formRequestClass = $this->findFormRequestFromFunction($controller, $action);
        }
        // Extract scenario from route
        $scenario = $this->extractScenarioFromRoute($route, $action, $entity);

        Log::channel('openapi')->info('Scenario and FormRequest detected', [
            'entity' => $entity,
            'action' => $action,
            'scenario' => $scenario,
            'form_request' => $formRequestClass,
        ]);

        $metadata = [
            'entity' => $entity,
            'entity_singular' => $this->getSingular($entity),
            'entity_plural' => $this->getPlural($entity),
            'entity_url' => Str::kebab($entity),
            'module' => $module,
            'action' => $action,
            'scenario' => $scenario,
            'table_name' => $this->getTableName($modelClass),
            'model_class' => $modelClass,
            'available_fields' => $this->getModelFields($modelClass),
            'available_relations' => json_encode($this->getModelRelations($modelClass)),
            'fillable_fields' => $this->getFillableFields($modelClass),
            'hidden_fields' => $this->getHiddenFields($modelClass),
            'casts' => $this->getCasts($modelClass),
            'has_relations' => $this->hasRelations($modelClass),

            'form_request_class' => $formRequestClass,
            'validation_rules' => $this->getValidationRulesFromFormRequest($formRequestClass, $scenario),
            'required_fields' => $this->getRequiredFieldsFromFormRequest($formRequestClass, $scenario),
            'has_validation' => $formRequestClass !== null,

            'model_schema' => $this->generateModelSchema($modelClass),
            'request_schema' => $this->generateRequestSchemaFromFormRequest($formRequestClass, $scenario),
            'request_example' => $this->generateRequestExampleFromFormRequest($formRequestClass, $scenario),
            'response_example' => $this->generateResponseExample($modelClass),

            'attr_examples' => $this->generateAttrExamples($modelClass),
            'oper_examples' => $this->generateOperExamples($modelClass),
            'orderby_examples' => $this->generateOrderByExamples($modelClass),

            'table_description' => $this->getTableDescription($entity, $module),
            'validation_description' => $this->formatValidationRulesFromFormRequest($formRequestClass, $scenario),
            'validation_errors_example' => $this->generateValidationErrorsExampleFromFormRequest($formRequestClass, $scenario),

            'relations_list' => $this->getRelationsList($modelClass),
            'relations_description' => $this->getRelationsDescription($modelClass),
        ];

        $this->cache[$cacheKey] = $metadata;

        Log::channel('openapi')->debug('Metadata extracted successfully with FormRequest', [
            'entity' => $entity,
            'scenario' => $scenario,
            'rules_count' => count($metadata['validation_rules']),
        ]);

        return $metadata;
    }

    protected function getRequiredFieldsFromFormRequest(?string $formRequestClass, string $scenario): array
    {
        $rules = $this->getValidationRulesFromFormRequest($formRequestClass, $scenario);
        $required = [];

        foreach ($rules as $field => $rule) {
            $ruleString = is_array($rule)
                ? implode('|', array_map(static fn($r) => is_string($r) ? $r : '', $rule))
                : (string)$rule;

            if (Str::contains($ruleString, 'required')) {
                $required[] = $field;
            }
        }

        return $required;
    }

    protected function generateRequestSchemaFromFormRequest(?string $formRequestClass, string $scenario): array
    {
        $rules = $this->getValidationRulesFromFormRequest($formRequestClass, $scenario);

        if (empty($rules)) {
            return [
                'type' => 'object',
                'properties' => [],
            ];
        }

        $properties = [];

        foreach ($rules as $field => $rule) {
            $ruleString = is_array($rule)
                ? implode('|', array_map(static fn($r) => is_string($r) ? $r : '', $rule))
                : (string)$rule;

            $properties[$field] = $this->ruleToSchema($ruleString, $field);
        }

        return [
            'type' => 'object',
            'properties' => $properties,
        ];
    }

    /**
     * Generate request example from FormRequest with Model fallback
     *
     * Priority:
     * 1. FormRequest validation rules
     * 2. Model fillable + casts
     * 3. Generic fallback
     *
     * @param string|null $formRequestClass FormRequest class
     * @param string $scenario Scenario name
     * @return array Request example
     */
    protected function generateRequestExampleFromFormRequest(?string $formRequestClass, string $scenario): array
    {
        // ==========================================
        // PRIORITY 1: FormRequest validation rules
        // ==========================================

        $rules = $this->getValidationRulesFromFormRequest($formRequestClass, $scenario);

        if (!empty($rules)) {
            $example = [];

            foreach ($rules as $field => $rule) {
                $example[$field] = $this->getExampleValue($rule, $field);
            }

            Log::channel('openapi')->info('✅ Request example from FormRequest', [
                'scenario' => $scenario,
                'form_request' => $formRequestClass ? class_basename($formRequestClass) : 'none',
                'fields_count' => count($example),
                'fields' => array_keys($example),
            ]);

            return $example;
        }

        // ==========================================
        // PRIORITY 2: Model fillable + casts
        // ==========================================

        Log::channel('openapi')->warning('⚠️ No FormRequest rules, trying Model fallback', [
            'form_request' => $formRequestClass ? class_basename($formRequestClass) : 'none',
            'scenario' => $scenario,
        ]);

        $modelClass = $this->findModelClass(
            $this->cache['current_entity'] ?? '',
            $this->cache['current_module'] ?? ''
        );

        if ($modelClass && class_exists($modelClass)) {
            try {
                $model = new $modelClass();

                // Get fillable fields
                $fillable = $model->getFillable();

                if (empty($fillable)) {
                    Log::channel('openapi')->debug('Model has no fillable fields, trying guarded approach');
                    // If no fillable, use all columns except guarded
                    $fillable = $this->getModelFields($modelClass);
                }

                // Get casts for type hints
                $casts = $model->getCasts();

                $example = [];

                foreach ($fillable as $field) {
                    // Skip timestamps and system fields
                    if (in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                        continue;
                    }

                    $castType = $casts[$field] ?? 'string';
                    $example[$field] = $this->getExampleValueFromCast($castType, $field);
                }

                Log::channel('openapi')->info('✅ Request example from Model (fallback)', [
                    'model' => class_basename($modelClass),
                    'fields_count' => count($example),
                    'fields' => array_keys($example),
                    'used_fillable' => !empty($model->getFillable()),
                ]);

                return $example;

            } catch (\Throwable $e) {
                Log::channel('openapi')->warning('⚠️ Failed to generate example from Model', [
                    'model' => $modelClass,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // ==========================================
        // PRIORITY 3: Generic fallback (last resort)
        // ==========================================

        Log::channel('openapi')->error('❌ Using generic request example (all strategies failed)', [
            'form_request' => $formRequestClass ? class_basename($formRequestClass) : 'none',
            'scenario' => $scenario,
            'model' => $modelClass ? class_basename($modelClass) : 'none',
        ]);

        return [
            'data' => 'Example data - please configure FormRequest or Model fillable',
        ];
    }

    /**
     * Get example value based on cast type
     *
     * @param string $castType Cast type from Model
     * @param string $field Field name
     * @return mixed Example value
     */
    protected function getExampleValueFromCast(string $castType, string $field): mixed
    {
        // Detect from field name
        if (Str::endsWith($field, '_id') || $field === 'id') {
            return 1;
        }

        if (Str::contains($field, ['email'])) {
            return 'user@example.com';
        }

        if (Str::contains($field, ['phone', 'tel'])) {
            return '+1234567890';
        }

        if (Str::contains($field, ['url', 'link', 'website'])) {
            return 'https://example.com';
        }

        if (Str::contains($field, ['password'])) {
            return 'password123';
        }

        if (Str::contains($field, ['date']) && !Str::contains($field, ['update', 'create', 'delete'])) {
            return now()->format('Y-m-d');
        }

        // Detect from cast type
        return match ($castType) {
            'int', 'integer' => 0,
            'float', 'double', 'decimal' => 0.0,
            'bool', 'boolean' => false,
            'array', 'json' => [],
            'date' => now()->format('Y-m-d'),
            'datetime', 'timestamp' => now()->format('Y-m-d H:i:s'),
            'collection' => [],
            default => '',
        };
    }

    protected function formatValidationRulesFromFormRequest(?string $formRequestClass, string $scenario): string
    {
        $rules = $this->getValidationRulesFromFormRequest($formRequestClass, $scenario);

        if (empty($rules)) {
            return 'No validation rules defined.';
        }

        $lines = [];
        foreach ($rules as $field => $rule) {
            $ruleStr = is_array($rule)
                ? implode(', ', array_map(static function ($r) {
                    return is_string($r) ? $r : get_class($r);
                }, $rule))
                : (string)$rule;

            $lines[] = "- **{$field}**: {$ruleStr}";
        }

        return implode("\n", $lines);
    }

    protected function generateValidationErrorsExampleFromFormRequest(?string $formRequestClass, string $scenario): array
    {
        $rules = $this->getValidationRulesFromFormRequest($formRequestClass, $scenario);

        if (empty($rules)) {
            return [
                'field' => ['The field is required.'],
            ];
        }

        $errors = [];
        foreach (array_slice(array_keys($rules), 0, 2) as $field) {
            $errors[$field] = ["The {$field} field is required."];
        }

        return $errors;
    }

    /**
     * Find FormRequest class for entity with multiple strategies
     *
     * Search order:
     * 1. Action-specific (ej: LoginRequest, RegisterRequest, ValidateRequest)
     * 2. Entity singular (ej: UserRequest)
     * 3. Entity plural (ej: UsersRequest)
     * 4. Global action-specific
     * 5. Global entity
     *
     * @param string $entity Entity name
     * @param string $module Module name
     * @param string|null $action Action name (optional)
     * @return string|null FormRequest class name
     */
    protected function findFormRequest(string $entity, string $module, ?string $action = null): ?string
    {
        // Prepare naming variants
        $entitySingular = Str::studly(Str::singular($entity));
        $entityPlural = Str::studly($entity);
        $actionStudly = $action ? Str::studly($action) : null;

        // Build search candidates
        $candidates = [];

        // 1. Action-specific in module (HIGHEST PRIORITY)
        if ($actionStudly) {
            $candidates[] = [
                'class' => "Modules\\{$module}\\Http\\Requests\\{$actionStudly}Request",
                'strategy' => 'action-specific-module',
                'priority' => 1,
            ];
        }

        // 2. Entity singular in module
        $candidates[] = [
            'class' => "Modules\\{$module}\\Http\\Requests\\{$entitySingular}Request",
            'strategy' => 'entity-singular-module',
            'priority' => 2,
        ];

        // 3. Entity plural in module
        $candidates[] = [
            'class' => "Modules\\{$module}\\Http\\Requests\\{$entityPlural}Request",
            'strategy' => 'entity-plural-module',
            'priority' => 3,
        ];

        // 4. Action-specific global
        if ($actionStudly) {
            $candidates[] = [
                'class' => "App\\Http\\Requests\\{$actionStudly}Request",
                'strategy' => 'action-specific-global',
                'priority' => 4,
            ];
        }

        // 5. Entity singular global
        $candidates[] = [
            'class' => "App\\Http\\Requests\\{$entitySingular}Request",
            'strategy' => 'entity-singular-global',
            'priority' => 5,
        ];

        // 6. Entity plural global
        $candidates[] = [
            'class' => "App\\Http\\Requests\\{$entityPlural}Request",
            'strategy' => 'entity-plural-global',
            'priority' => 6,
        ];

        // Search in priority order
        foreach ($candidates as $candidate) {
            $className = $candidate['class'];

            if (class_exists($className)) {
                Log::channel('openapi')->info('✅ FormRequest found', [
                    'entity' => $entity,
                    'module' => $module,
                    'action' => $action,
                    'class' => $className,
                    'strategy' => $candidate['strategy'],
                    'priority' => $candidate['priority'],
                ]);

                return $className;
            }
        }

        // Not found - detailed logging
        Log::channel('openapi')->warning('❌ FormRequest not found', [
            'entity' => $entity,
            'module' => $module,
            'action' => $action,
            'tried_classes' => array_column($candidates, 'class'),
            'tried_strategies' => array_column($candidates, 'strategy'),
        ]);

        return null;
    }

    /**
     * Find FormRequest class from controller method signature
     *
     * Extracts FormRequest from method parameters, ignoring base Laravel Request.
     * Perfect for endpoints like login(), register(), store(), update(), etc.
     *
     * @param string $controller Full controller class name (e.g., 'App\Http\Controllers\AuthController')
     * @param string $method Method name (e.g., 'login', 'register', 'store')
     * @return string|null FormRequest FQCN or null if not found
     *
     * @example
     * // Controller:
     * public function login(LoginRequest $request): JsonResponse { ... }
     *
     * // Usage:
     * $formRequest = $this->findFormRequestFromFunction(
     *     'App\Http\Controllers\AuthController',
     *     'login'
     * );
     * // Returns: 'App\Http\Requests\LoginRequest'
     */
    protected function findFormRequestFromFunction(string $controller, string $method): ?string
    {
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // 1. Validate controller exists
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

        if (!class_exists($controller)) {
            Log::channel('openapi')->debug('findFormRequestFromFunction: Controller not found', [
                'controller' => $controller,
                'method' => $method,
            ]);
            return null;
        }

        try {
            $reflection = new ReflectionClass($controller);

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // 2. Check method exists (with aliases support)
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

            $methodAliases = [
                'index' => 'list',
                'list' => 'index',
                'store' => 'create',
                'create' => 'store',
                'edit' => 'update',
            ];

            $methodsToTry = [$method];
            if (isset($methodAliases[$method])) {
                $methodsToTry[] = $methodAliases[$method];
            }

            $reflectionMethod = null;
            $actualMethod = null;

            foreach ($methodsToTry as $methodName) {
                if ($reflection->hasMethod($methodName)) {
                    $reflectionMethod = $reflection->getMethod($methodName);
                    $actualMethod = $methodName;
                    break;
                }
            }

            if (!$reflectionMethod) {
                Log::channel('openapi')->debug('findFormRequestFromFunction: Method not found', [
                    'controller' => $controller,
                    'method' => $method,
                    'tried' => $methodsToTry,
                ]);
                return null;
            }

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // 3. Extract parameters from method signature
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

            $parameters = $reflectionMethod->getParameters();

            foreach ($parameters as $parameter) {
                $type = $parameter->getType();

                // Skip if no type hint or is builtin type (string, int, etc.)
                if (!$type || $type->isBuiltin()) {
                    continue;
                }

                $className = $type->getName();

                // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                // 4. ✅ CRITICAL: Skip base Laravel Request
                // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

                if ($className === 'Illuminate\Http\Request') {
                    Log::channel('openapi')->debug('findFormRequestFromFunction: Skipping base Request', [
                        'controller' => class_basename($controller),
                        'method' => $actualMethod,
                        'parameter' => $parameter->getName(),
                    ]);
                    continue;
                }

                // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                // 5. ✅ Check if it's a FormRequest subclass
                // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

                if (is_subclass_of($className, 'Illuminate\Foundation\Http\FormRequest')) {
                    Log::channel('openapi')->info('✅ findFormRequestFromFunction: FormRequest FOUND', [
                        'controller' => class_basename($controller),
                        'method' => $actualMethod,
                        'form_request' => class_basename($className),
                        'full_class' => $className,
                        'parameter_name' => $parameter->getName(),
                        'parameter_position' => $parameter->getPosition(),
                    ]);

                    return $className;
                }

                // Log if it's another type of Request
                if (str_contains($className, 'Request')) {
                    Log::channel('openapi')->debug('findFormRequestFromFunction: Found Request class but not FormRequest', [
                        'controller' => class_basename($controller),
                        'method' => $actualMethod,
                        'class' => $className,
                        'is_form_request' => false,
                    ]);
                }
            }

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // 6. No FormRequest found
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

            Log::channel('openapi')->debug('findFormRequestFromFunction: No FormRequest found', [
                'controller' => class_basename($controller),
                'method' => $actualMethod,
                'parameters_count' => count($parameters),
            ]);

            return null;

        } catch (\ReflectionException $e) {
            Log::channel('openapi')->error('findFormRequestFromFunction: Reflection error', [
                'controller' => $controller,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get validation rules from FormRequest with scenario validation and fallbacks
     *
     * @param string|null $formRequestClass FormRequest class name
     * @param string $scenario Scenario name
     * @return array Validation rules
     */
    protected function getValidationRulesFromFormRequest(?string $formRequestClass, string $scenario): array
    {
        if (!$formRequestClass || !class_exists($formRequestClass)) {
            Log::channel('openapi')->debug('No FormRequest class provided or class does not exist');
            return [];
        }

        try {
            // ==========================================
            // STRATEGY 1: getRulesForScenario() method
            // ==========================================

            if (method_exists($formRequestClass, 'getRulesForScenario')) {
                $instance = new $formRequestClass();
                $rules = $instance->getRulesForScenario($scenario);
                if (count($rules) == 0) {
                    Log::channel('openapi')->error('❌ No scenario rules found', [
                        'requested' => $scenario,
                        'formRequestClass' => $formRequestClass,
                    ]);
                }
                Log::channel('openapi')->info('✅ Rules extracted via getRulesForScenario()', [
                    'class' => class_basename($formRequestClass),
                    'scenario' => $scenario,
                    'rules_count' => count($rules),
                ]);

                return $this->cleanRulesArray($rules);
            }

            // ==========================================
            // STRATEGY 2: rules() method with scenario key
            // ==========================================

            $reflection = new \ReflectionClass($formRequestClass);

            if ($reflection->hasMethod('rules')) {
                $instance = $reflection->newInstanceWithoutConstructor();
                $allRules = $instance->rules();

                if (!isset($allRules[$scenario])) {
                    $availableScenarios = array_keys($allRules);

                    Log::channel('openapi')->warning('⚠️ Scenario not found in FormRequest', [
                        'class' => class_basename($formRequestClass),
                        'requested_scenario' => $scenario,
                        'available_scenarios' => $availableScenarios,
                    ]);

                    $scenarioFallbacks = [
                        'create' => ['store'],
                        'store' => ['create'],
                        'update' => ['edit'],
                        'edit' => ['update'],
                        'list' => ['index'],
                        'index' => ['list'],
                        'delete' => ['destroy'],
                        'destroy' => ['delete'],
                    ];

                    foreach ($scenarioFallbacks[$scenario] ?? [] as $fallbackScenario) {
                        if (isset($allRules[$fallbackScenario])) {
                            Log::channel('openapi')->info('✅ Using fallback scenario', [
                                'requested' => $scenario,
                                'using' => $fallbackScenario,
                            ]);

                            return $this->cleanRulesArray($allRules[$fallbackScenario]);
                        }
                    }

                    if (count($availableScenarios) === 1) {
                        $onlyScenario = $availableScenarios[0];

                        Log::channel('openapi')->info('✅ Using only available scenario', [
                            'requested' => $scenario,
                            'using' => $onlyScenario,
                        ]);

                        return $this->cleanRulesArray($allRules[$onlyScenario]);
                    }

                    if (!empty($allRules) && !isset($allRules[0])) {
                        // Check if it's a simple rules array (field => rules)
                        $firstKey = array_key_first($allRules);
                        if (is_string($firstKey) && !in_array($firstKey, ['create', 'update', 'delete', 'list'])) {
                            Log::channel('openapi')->info('✅ Using direct rules (no scenarios)', [
                                'class' => class_basename($formRequestClass),
                                'rules_count' => count($allRules),
                            ]);

                            return $this->cleanRulesArray($allRules);
                        }
                    }

                    Log::channel('openapi')->error('❌ No suitable rules found', [
                        'requested' => $scenario,
                        'available' => $availableScenarios,
                    ]);

                    return [];
                }

                Log::channel('openapi')->info('✅ Rules extracted via rules() method', [
                    'class' => class_basename($formRequestClass),
                    'scenario' => $scenario,
                    'rules_count' => count($allRules[$scenario]),
                ]);

                return $this->cleanRulesArray($allRules[$scenario]);
            }

            // ==========================================
            // STRATEGY 3: $rulesByScenario property
            // ==========================================

            if ($reflection->hasProperty('rulesByScenario')) {
                $property = $reflection->getProperty('rulesByScenario');
                $property->setAccessible(true);

                $instance = $reflection->newInstanceWithoutConstructor();
                $allRules = $property->getValue($instance);

                if (isset($allRules[$scenario])) {
                    Log::channel('openapi')->info('✅ Rules extracted via $rulesByScenario property', [
                        'class' => class_basename($formRequestClass),
                        'scenario' => $scenario,
                        'rules_count' => count($allRules[$scenario]),
                    ]);

                    return $this->cleanRulesArray($allRules[$scenario]);
                }
            }

            // ==========================================
            // No rules found
            // ==========================================

            Log::channel('openapi')->warning('⚠️ FormRequest found but no rules extracted', [
                'class' => class_basename($formRequestClass),
                'scenario' => $scenario,
                'has_getRulesForScenario' => method_exists($formRequestClass, 'getRulesForScenario'),
                'has_rules_method' => $reflection->hasMethod('rules'),
                'has_rulesByScenario' => $reflection->hasProperty('rulesByScenario'),
            ]);

        } catch (\Throwable $e) {
            Log::channel('openapi')->error('❌ Exception extracting rules from FormRequest', [
                'class' => class_basename($formRequestClass),
                'scenario' => $scenario,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return [];
    }


    /**
     * Extract scenario from route
     *
     * Supports both Route object and route array
     *
     * Priority:
     * 1. Middleware inject:_scenario=xxx
     * 2. URI pattern detection
     * 3. Action-based default
     *
     * @param mixed $route Route object or array (supports Illuminate\Routing\Route or array)
     * @param string $action Action name
     * @param string $entity Entity name
     * @return string Scenario name
     */
    protected function extractScenarioFromRoute(mixed $route, string $action, string $entity): string
    {
        if (!$route) {
            $scenario = $this->getDefaultScenario($action);
            Log::channel('openapi')->debug('Using default scenario (no route)', ['scenario' => $scenario]);
            return $scenario;
        }

        // Handle both Route object and array
        $middleware = [];
        $uri = '';

        if (is_object($route)) {
            // ✅ IMPROVED: Prefer gatherMiddleware() over middleware()
            if (method_exists($route, 'gatherMiddleware')) {
                $middleware = $route->gatherMiddleware();
                Log::channel('openapi')->debug('Using gatherMiddleware()', ['count' => count($middleware)]);
            } elseif (method_exists($route, 'middleware')) {
                $middleware = $route->middleware();
                Log::channel('openapi')->debug('Using middleware()', ['count' => count($middleware)]);
            }

            $uri = method_exists($route, 'uri') ? $route->uri() : '';
        } elseif (is_array($route)) {
            $middleware = $route['middleware'] ?? [];
            $uri = $route['uri'] ?? '';
        }

        // 1. Check middleware for inject:_scenario=xxx
        foreach ($middleware as $mw) {
            // ✅ IMPROVED: Handle middleware objects
            if (is_object($mw)) {
                if (method_exists($mw, 'getName')) {
                    $mw = $mw->getName();
                } else {
                    $mw = get_class($mw);
                }
            }

            if (is_string($mw) && Str::startsWith($mw, 'inject:_scenario=')) {
                $scenario = Str::after($mw, 'inject:_scenario=');

                Log::channel('openapi')->info('✅ Scenario from middleware', [
                    'middleware' => $mw,
                    'scenario' => $scenario,
                ]);

                return $scenario;
            }
        }

        // 2. Detect from URI pattern
        if (Str::endsWith($uri, '/validate')) {
            Log::channel('openapi')->info('✅ Scenario from URI pattern (/validate)');
            return 'validate';
        }

        if (Str::contains($uri, '/bulk_')) {
            $parts = explode('/', $uri);
            foreach ($parts as $part) {
                if (Str::startsWith($part, 'bulk_')) {
                    Log::channel('openapi')->info('✅ Scenario from URI pattern (bulk_*)', ['scenario' => $part]);
                    return $part;
                }
            }
        }

        // 3. Default scenario
        $scenario = $this->getDefaultScenario($action);
        Log::channel('openapi')->debug('Using default scenario', ['scenario' => $scenario]);
        return $scenario;
    }

    /**
     * Get default scenario based on action
     */
    protected function getDefaultScenario(string $action): string
    {
        $mapping = [
            'create' => 'create',
            'store' => 'create',
            'update' => 'update',
            'edit' => 'update',
            'list' => 'list',
            'index' => 'list',
            'show' => 'show',
            'delete' => 'delete',
            'destroy' => 'delete',
        ];

        return $mapping[$action] ?? $action;
    }

    /**
     * ⚠️ FIXED: Get validation rules by scenario from Rule class
     *
     * CRITICAL FIX: Uses reflection to read $rules array WITHOUT executing code
     * This prevents errors when rules use $this->connection or other instance properties
     *
     * @param string|null $ruleClass Rule class name
     * @param string $scenario Scenario name
     * @return array Validation rules for scenario
     */
    protected function getValidationRulesByScenario(?string $ruleClass, string $scenario): array
    {
        if (!$ruleClass) {
            Log::channel('openapi')->debug('No rule class provided');
            return [];
        }

        Log::channel('openapi')->info('🔍 EXTRACTING VALIDATION RULES', [
            'class' => $ruleClass,
            'scenario' => $scenario,
        ]);

        // STRATEGY 1: Reflection
        try {
            Log::channel('openapi')->debug('  Trying Strategy 1: Reflection...');
            $rules = $this->extractRulesViaReflection($ruleClass, $scenario);
            if (!empty($rules)) {
                Log::channel('openapi')->info('✅ SUCCESS: Strategy 1 (Reflection)', [
                    'rules_count' => count($rules),
                ]);
                return $rules;
            }
            Log::channel('openapi')->debug('  Strategy 1 returned empty');
        } catch (\Throwable $e) {
            Log::channel('openapi')->warning('⚠️ Strategy 1 failed', [
                'error' => $e->getMessage(),
            ]);
        }

        // STRATEGY 2: Safe instantiation
        try {
            Log::channel('openapi')->debug('  Trying Strategy 2: Safe instantiation...');
            $instance = $this->instantiateRuleClass($ruleClass);

            if ($instance) {
                $rules = $this->extractRulesFromInstanceSafely($instance, $scenario);
                if (!empty($rules)) {
                    Log::channel('openapi')->info('✅ SUCCESS: Strategy 2 (Safe instantiation)', [
                        'rules_count' => count($rules),
                    ]);
                    return $rules;
                }
                Log::channel('openapi')->debug('  Strategy 2 returned empty');
            } else {
                Log::channel('openapi')->debug('  Could not instantiate');
            }
        } catch (\Throwable $e) {
            Log::channel('openapi')->warning('⚠️ Strategy 2 failed', [
                'error' => $e->getMessage(),
            ]);
        }

        // STRATEGY 3: File parsing
        try {
            Log::channel('openapi')->debug('  Trying Strategy 3: File parsing...');
            $rules = $this->extractRulesFromFile($ruleClass, $scenario);
            if (!empty($rules)) {
                Log::channel('openapi')->info('✅ SUCCESS: Strategy 3 (File parsing)', [
                    'rules_count' => count($rules),
                ]);
                return $rules;
            }
            Log::channel('openapi')->debug('  Strategy 3 returned empty');
        } catch (\Throwable $e) {
            Log::channel('openapi')->warning('⚠️ Strategy 3 failed', [
                'error' => $e->getMessage(),
            ]);
        }

        Log::channel('openapi')->error('❌ ALL STRATEGIES FAILED for validation rules', [
            'rule_class' => $ruleClass,
            'scenario' => $scenario,
        ]);

        return [];
    }

    /**
     * ⚠️ CRITICAL FIX: Extract rules via reflection WITHOUT executing code
     *
     * This method reads the default value of the public $rules property
     * WITHOUT creating an instance, which prevents $this->connection errors
     *
     * @param string $ruleClass Rule class name
     * @param string $scenario Scenario name
     * @return array Rules for scenario
     */
    protected function extractRulesViaReflection(string $ruleClass, string $scenario): array
    {
        try {
            $reflection = new ReflectionClass($ruleClass);

            // CASE 1: Public property $rules
            if ($reflection->hasProperty('rules')) {
                $property = $reflection->getProperty('rules');
                $property->setAccessible(true);

                $defaultProperties = $reflection->getDefaultProperties();

                if (isset($defaultProperties['rules'])) {
                    $allRules = $defaultProperties['rules'];

                    if (isset($allRules[$scenario])) {
                        $scenarioRules = $allRules[$scenario];
                        $cleanRules = $this->cleanRulesArray($scenarioRules);

                        Log::channel('openapi')->debug('  Found rules via property', [
                            'original_count' => count($scenarioRules),
                            'cleaned_count' => count($cleanRules),
                        ]);

                        return $cleanRules;
                    } else {
                        Log::channel('openapi')->debug('  Scenario not found in $rules', [
                            'available_scenarios' => array_keys($allRules),
                            'requested' => $scenario,
                        ]);
                    }
                }
            }

            // CASE 2: Method getRules()
            if ($reflection->hasMethod('getRules')) {
                Log::channel('openapi')->debug('  Trying getRules() method...');

                $instance = $this->instantiateRuleClass($ruleClass);
                if ($instance) {
                    $allRules = $instance->getRules();

                    if (isset($allRules[$scenario])) {
                        $scenarioRules = $allRules[$scenario];
                        $cleanRules = $this->cleanRulesArray($scenarioRules);

                        Log::channel('openapi')->debug('  Found rules via getRules()', [
                            'cleaned_count' => count($cleanRules),
                        ]);

                        return $cleanRules;
                    }
                }
            }

            // CASE 3: Scenario-specific method (e.g., createRules())
            $scenarioMethod = $scenario . 'Rules';
            if ($reflection->hasMethod($scenarioMethod)) {
                Log::channel('openapi')->debug("  Trying {$scenarioMethod}() method...");

                $instance = $this->instantiateRuleClass($ruleClass);
                if ($instance) {
                    $rules = $instance->$scenarioMethod();
                    $cleanRules = $this->cleanRulesArray($rules);

                    Log::channel('openapi')->debug("  Found rules via {$scenarioMethod}()", [
                        'cleaned_count' => count($cleanRules),
                    ]);

                    return $cleanRules;
                }
            }

            return [];

        } catch (\Throwable $e) {
            Log::channel('openapi')->error('❌ Reflection extraction failed', [
                'class' => $ruleClass,
                'scenario' => $scenario,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Clean rules array (remove non-string rules like objects)
     *
     * @param array $rules Original rules
     * @return array Cleaned rules
     */
    protected function cleanRulesArray(array $rules): array
    {
        $cleanRules = [];

        foreach ($rules as $field => $rule) {
            if (is_string($rule)) {
                $cleanRules[$field] = $rule;
            } elseif (is_array($rule)) {
                $cleanArray = [];
                foreach ($rule as $singleRule) {
                    if (is_string($singleRule)) {
                        $cleanArray[] = $singleRule;
                    }
                }
                if (!empty($cleanArray)) {
                    $cleanRules[$field] = $cleanArray;
                }
            }
        }

        return $cleanRules;
    }

    protected function convertRuleObjectToString($ruleObject): ?string
    {
        $className = get_class($ruleObject);

        // Common Laravel validation rules
        if (str_contains($className, 'Illuminate\\Validation\\Rules\\Unique')) {
            return 'unique';
        }

        if (str_contains($className, 'Illuminate\\Validation\\Rules\\Exists')) {
            return 'exists';
        }

        if (str_contains($className, 'Illuminate\\Validation\\Rules\\In')) {
            return 'in';
        }

        if (str_contains($className, 'Illuminate\\Validation\\Rules\\Password')) {
            return 'password';
        }

        // Custom rules with __toString
        if (method_exists($ruleObject, '__toString')) {
            return (string)$ruleObject;
        }

        // Fallback: use class basename
        Log::channel('openapi')->debug('Converting unknown Rule object', [
            'class' => $className,
            'basename' => class_basename($className),
        ]);

        return strtolower(class_basename($className));
    }

    /**
     * Extract rules from instance SAFELY (catching $this errors)
     *
     * @param object $instance Rule instance
     * @param string $scenario Scenario name
     * @return array Rules
     */
    protected function extractRulesFromInstanceSafely($instance, string $scenario): array
    {
        try {
            $reflection = new \ReflectionObject($instance);

            if ($reflection->hasProperty('rules')) {
                $property = $reflection->getProperty('rules');
                $property->setAccessible(true);

                $allRules = $property->getValue($instance);

                if (isset($allRules[$scenario])) {
                    return $this->cleanRulesArray($allRules[$scenario]);
                }
            }
        } catch (\Throwable $e) {
            Log::channel('openapi')->debug('Instance property access failed', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback to method-based extraction
        try {
            $scenarioMethod = $scenario . 'Rules';
            if (method_exists($instance, $scenarioMethod)) {
                return $instance->$scenarioMethod();
            }

            $methods = ['rules', 'getRules', 'createRules', 'storeRules'];
            foreach ($methods as $method) {
                if (method_exists($instance, $method)) {
                    return $instance->$method();
                }
            }
        } catch (\Throwable $e) {
            Log::channel('openapi')->debug('Method-based extraction failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * ⚠️ ENHANCED: Extract rules from file using AST-like parsing
     *
     * @param string $ruleClass Rule class name
     * @param string $scenario Scenario name
     * @return array Rules
     */
    protected function extractRulesFromFile(string $ruleClass, string $scenario): array
    {
        $path = base_path(str_replace('\\', '/', $ruleClass) . '.php');

        if (!file_exists($path)) {
            Log::channel('openapi')->debug('Rule file not found', ['path' => $path]);
            return [];
        }

        $content = file_get_contents($path);

        // Pattern to find: public array $rules = [
        $pattern = '/public\s+array\s+\$rules\s*=\s*\[(.*?)\];/s';

        if (!preg_match($pattern, $content, $matches)) {
            Log::channel('openapi')->debug('No $rules property found in file');
            return [];
        }

        $rulesContent = $matches[1];

        // Extract scenario array
        $scenarioPattern = "/['\"]" . preg_quote($scenario, '/') . "['\"]\s*=>\s*\[(.*?)\],?\s*(?:['\"]|$)/s";

        if (!preg_match($scenarioPattern, $rulesContent, $scenarioMatches)) {
            Log::channel('openapi')->debug('Scenario not found in $rules', ['scenario' => $scenario]);
            return [];
        }

        $scenarioContent = $scenarioMatches[1];

        // Parse field => rule pairs
        $rules = [];
        $fieldPattern = "/['\"]([^'\"]+)['\"]\s*=>\s*(?:\[(.*?)\]|['\"]([^'\"]+)['\"])/s";

        if (preg_match_all($fieldPattern, $scenarioContent, $fieldMatches, PREG_SET_ORDER)) {
            foreach ($fieldMatches as $match) {
                $field = $match[1];

                if (!empty($match[3])) {
                    // String rule
                    $rules[$field] = $match[3];
                } elseif (!empty($match[2])) {
                    // Array rule - extract string parts only
                    $arrayContent = $match[2];
                    $ruleArray = [];

                    if (preg_match_all("/['\"]([^'\"]+)['\"]/", $arrayContent, $ruleMatches)) {
                        $ruleArray = $ruleMatches[1];
                    }

                    if (!empty($ruleArray)) {
                        $rules[$field] = $ruleArray;
                    }
                }
            }
        }

        Log::channel('openapi')->debug('Rules extracted from file', [
            'rules_count' => count($rules),
        ]);

        return $rules;
    }

    /**
     * Instantiate Rule class safely
     */
    protected function instantiateRuleClass(string $ruleClass): ?object
    {
        try {
            $reflection = new ReflectionClass($ruleClass);

            if (!$reflection->getConstructor()) {
                return new $ruleClass();
            }

            $constructor = $reflection->getConstructor();
            $params = $constructor->getParameters();

            if (empty($params) || $this->allParamsAreOptional($params)) {
                return new $ruleClass();
            }

            return $reflection->newInstanceWithoutConstructor();

        } catch (\Throwable $e) {
            Log::channel('openapi')->debug('Failed to instantiate Rule class', [
                'class' => $ruleClass,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if all params are optional
     */
    protected function allParamsAreOptional(array $params): bool
    {
        foreach ($params as $param) {
            if (!$param->isOptional()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get required fields by scenario
     */
    protected function getRequiredFieldsByScenario(?string $ruleClass, string $scenario): array
    {
        $rules = $this->getValidationRulesByScenario($ruleClass, $scenario);
        $required = [];

        foreach ($rules as $field => $rule) {
            $ruleString = is_array($rule)
                ? implode('|', array_map(static fn($r) => is_string($r) ? $r : '', $rule))
                : (string)$rule;

            if (Str::contains($ruleString, 'required')) {
                $required[] = $field;
            }
        }

        return $required;
    }

    /**
     * Generate request schema by scenario
     */
    protected function generateRequestSchemaByScenario(?string $ruleClass, string $scenario): array
    {
        $rules = $this->getValidationRulesByScenario($ruleClass, $scenario);

        if (empty($rules)) {
            return [
                'type' => 'object',
                'properties' => [],
            ];
        }

        $properties = [];

        foreach ($rules as $field => $rule) {
            $ruleString = is_array($rule)
                ? implode('|', array_map(static fn($r) => is_string($r) ? $r : '', $rule))
                : (string)$rule;

            $properties[$field] = $this->ruleToSchema($ruleString, $field);
        }

        return [
            'type' => 'object',
            'properties' => $properties,
        ];
    }

    /**
     * ⚠️ CRITICAL: Generate request example by scenario
     *
     * Returns FLAT array, NOT wrapped in "data"
     */
    protected function generateRequestExampleByScenario(?string $ruleClass, string $scenario): array
    {
        $rules = $this->getValidationRulesByScenario($ruleClass, $scenario);

        if (empty($rules)) {
            return [];
        }

        $example = [];

        foreach ($rules as $field => $rule) {
            $example[$field] = $this->getExampleValue($rule, $field);
        }

        return $example;
    }

    /**
     * Format validation rules by scenario
     */
    protected function formatValidationRulesByScenario(?string $ruleClass, string $scenario): string
    {
        $rules = $this->getValidationRulesByScenario($ruleClass, $scenario);

        if (empty($rules)) {
            return 'No validation rules defined.';
        }

        $lines = [];
        foreach ($rules as $field => $rule) {
            $ruleStr = is_array($rule)
                ? implode(', ', array_map(static function ($r) {
                    return is_string($r) ? $r : get_class($r);
                }, $rule))
                : (string)$rule;

            $lines[] = "- **{$field}**: {$ruleStr}";
        }

        return implode("\n", $lines);
    }

    /**
     * Generate validation errors example by scenario
     */
    protected function generateValidationErrorsExampleByScenario(?string $ruleClass, string $scenario): array
    {
        $rules = $this->getValidationRulesByScenario($ruleClass, $scenario);

        if (empty($rules)) {
            return [
                'field' => ['The field is required.'],
            ];
        }

        $errors = [];
        foreach (array_slice(array_keys($rules), 0, 2) as $field) {
            $errors[$field] = ["The {$field} field is required."];
        }

        return $errors;
    }

    protected function getSingular(string $entity): string
    {
        return Str::title(str_replace(['-', '_'], ' ', Str::singular($entity)));
    }

    protected function getPlural(string $entity): string
    {
        return Str::title(str_replace(['-', '_'], ' ', Str::plural($entity)));
    }

    protected function getTableName(?string $modelClass): ?string
    {
        if (!$modelClass || !class_exists($modelClass)) {
            return null;
        }

        try {
            $model = new $modelClass();
            return $model->getTable();
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function getModelFields(?string $modelClass): array
    {
        if (!$modelClass || !class_exists($modelClass)) {
            return [];
        }

        try {
            $model = new $modelClass();
            $fillable = $model->getFillable();
            $casts = $model->getCasts();

            $fields = [];
            foreach ($fillable as $field) {
                $type = $casts[$field] ?? $this->inferFieldType($field);
                $fields[$field] = $type;
            }

            return $fields;
        } catch (\Exception $e) {
            Log::channel('openapi')->warning('Failed to extract model fields', [
                'model' => $modelClass,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    protected function getFillableFields(?string $modelClass): array
    {
        if (!$modelClass || !class_exists($modelClass)) {
            return [];
        }

        try {
            $model = new $modelClass();
            return $model->getFillable();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getHiddenFields(?string $modelClass): array
    {
        if (!$modelClass || !class_exists($modelClass)) {
            return [];
        }

        try {
            $model = new $modelClass();
            return $model->getHidden();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getCasts(?string $modelClass): array
    {
        if (!$modelClass || !class_exists($modelClass)) {
            return [];
        }

        try {
            $model = new $modelClass();
            return $model->getCasts();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function inferFieldType(string $fieldName): string
    {
        if (Str::endsWith($fieldName, '_id')) {
            return 'integer';
        }

        if (Str::startsWith($fieldName, ['is_', 'has_', 'can_'])) {
            return 'boolean';
        }

        if (Str::endsWith($fieldName, ['_at', '_date'])) {
            return 'datetime';
        }

        if (in_array($fieldName, ['price', 'amount', 'quantity', 'total', 'count'], true)) {
            return 'number';
        }

        if ($fieldName === 'email') {
            return 'string';
        }

        return 'string';
    }

    protected function getModelRelations(?string $modelClass): array
    {
        if (!$modelClass || !class_exists($modelClass)) {
            return [];
        }

        try {
            $reflection = new ReflectionClass($modelClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            $relations = [];

            foreach ($methods as $method) {
                if ($method->class !== $modelClass) {
                    continue;
                }

                if (Str::startsWith($method->name, '__')) {
                    continue;
                }

                $returnType = $method->getReturnType();
                if (!$returnType) {
                    continue;
                }

                $returnTypeName = $returnType->getName();

                if ($this->isRelationType($returnTypeName)) {
                    $relationType = class_basename($returnTypeName);

                    $relations[$method->name] = [
                        'name' => $method->name,
                        'type' => $relationType,
                        'supports_nesting' => true,
                    ];
                }
            }

            return $relations;
        } catch (\Exception $e) {
            Log::channel('openapi')->warning('Failed to extract relations', [
                'model' => $modelClass,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    protected function isRelationType(string $typeName): bool
    {
        $relationTypes = [
            'Illuminate\Database\Eloquent\Relations\HasOne',
            'Illuminate\Database\Eloquent\Relations\HasMany',
            'Illuminate\Database\Eloquent\Relations\BelongsTo',
            'Illuminate\Database\Eloquent\Relations\BelongsToMany',
            'Illuminate\Database\Eloquent\Relations\MorphTo',
            'Illuminate\Database\Eloquent\Relations\MorphOne',
            'Illuminate\Database\Eloquent\Relations\MorphMany',
            'Illuminate\Database\Eloquent\Relations\MorphToMany',
            'Illuminate\Database\Eloquent\Relations\HasOneThrough',
            'Illuminate\Database\Eloquent\Relations\HasManyThrough',
        ];

        foreach ($relationTypes as $relationType) {
            if ($typeName === $relationType || is_subclass_of($typeName, $relationType)) {
                return true;
            }
        }

        return false;
    }

    protected function hasRelations(?string $modelClass): bool
    {
        return !empty($this->getModelRelations($modelClass));
    }

    protected function getRelationsList(?string $modelClass): array
    {
        $relations = $this->getModelRelations($modelClass);
        return array_keys($relations);
    }

    protected function getRelationsDescription(?string $modelClass): string
    {
        $relations = $this->getModelRelations($modelClass);

        if (empty($relations)) {
            return 'No relations available.';
        }

        $descriptions = [];
        foreach ($relations as $name => $info) {
            $descriptions[] = "- **{$name}** ({$info['type']})";
        }

        return implode("\n", $descriptions);
    }

    protected function generateModelSchema(?string $modelClass): array
    {
        $fields = $this->getModelFields($modelClass);

        if (empty($fields)) {
            return [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                ],
            ];
        }

        $properties = [
            'id' => ['type' => 'integer', 'example' => 1],
        ];

        foreach ($fields as $field => $type) {
            $properties[$field] = $this->fieldTypeToSchema($type, $field);
        }

        $properties['created_at'] = [
            'type' => 'string',
            'format' => 'date-time',
            'example' => '2024-12-25T10:00:00Z',
        ];
        $properties['updated_at'] = [
            'type' => 'string',
            'format' => 'date-time',
            'example' => '2024-12-25T10:00:00Z',
        ];

        return [
            'type' => 'object',
            'properties' => $properties,
        ];
    }

    protected function fieldTypeToSchema(string $type, string $fieldName): array
    {
        return match ($type) {
            'integer', 'int' => ['type' => 'integer', 'example' => 1],
            'boolean', 'bool' => ['type' => 'boolean', 'example' => false],
            'datetime', 'timestamp' => [
                'type' => 'string',
                'format' => 'date-time',
                'example' => '2024-12-25T10:00:00Z',
            ],
            'date' => ['type' => 'string', 'format' => 'date', 'example' => '2024-12-25'],
            'float', 'double', 'decimal', 'number' => ['type' => 'number', 'example' => 0.0],
            'array', 'json' => ['type' => 'array', 'items' => ['type' => 'string']],
            default => ['type' => 'string', 'example' => ''],
        };
    }

    protected function ruleToSchema(string $rule, string $fieldName): array
    {
        $schema = ['type' => 'string'];

        if (Str::contains($rule, ['integer', 'numeric'])) {
            $schema['type'] = Str::contains($rule, 'integer') ? 'integer' : 'number';
            $schema['example'] = 0;
        }

        if (Str::contains($rule, 'boolean')) {
            $schema['type'] = 'boolean';
            $schema['example'] = false;
        }

        if (Str::contains($rule, 'array')) {
            $schema['type'] = 'array';
            $schema['items'] = ['type' => 'string'];
            $schema['example'] = [];
        }

        if (Str::contains($rule, 'email')) {
            $schema['format'] = 'email';
            $schema['example'] = 'user@example.com';
        }

        if (Str::contains($rule, 'url')) {
            $schema['format'] = 'uri';
            $schema['example'] = 'https://example.com';
        }

        if (Str::contains($rule, 'date')) {
            $schema['format'] = 'date';
            $schema['example'] = '2024-12-25';
        }

        if (preg_match('/min:(\d+)/', $rule, $matches)) {
            $schema['minLength'] = (int)$matches[1];
        }
        if (preg_match('/max:(\d+)/', $rule, $matches)) {
            $schema['maxLength'] = (int)$matches[1];
        }

        if ($schema['type'] === 'string' && !isset($schema['example'])) {
            $schema['example'] = '';
        }

        return $schema;
    }

    protected function getExampleValue($rule, string $fieldName)
    {
        $ruleString = is_array($rule)
            ? implode('|', array_map(static fn($r) => is_string($r) ? $r : '', $rule))
            : (string)$rule;

        if (Str::contains($ruleString, 'boolean')) {
            return false;
        }

        if (Str::contains($ruleString, 'integer')) {
            return 0;
        }

        if (Str::contains($ruleString, 'numeric')) {
            return 0.0;
        }

        if (Str::contains($ruleString, 'array')) {
            return [];
        }

        if (Str::contains($ruleString, 'email') || $fieldName === 'email') {
            return '';
        }

        return '';
    }

    protected function generateResponseExample(?string $modelClass): array
    {
        $schema = $this->generateModelSchema($modelClass);
        $properties = $schema['properties'] ?? [];

        $example = [];

        foreach ($properties as $field => $def) {
            $example[$field] = $def['example'] ?? null;
        }

        return $example;
    }

    protected function generateAttrExamples(?string $modelClass): array
    {
        $fields = $this->getModelFields($modelClass);

        if (empty($fields)) {
            return [
                'status' => 'active',
                'type' => ['type1', 'type2'],
            ];
        }

        $examples = [];
        $count = 0;

        foreach ($fields as $field => $type) {
            if ($count >= 2) {
                break;
            }

            if ($type === 'string') {
                $examples[$field] = 'value';
            } elseif ($type === 'integer') {
                $examples[$field] = [1, 2, 3];
            } elseif ($type === 'boolean') {
                $examples[$field] = true;
            }

            $count++;
        }

        return $examples ?: ['status' => 'active'];
    }

    protected function generateOperExamples(?string $modelClass): array
    {
        $fields = $this->getModelFields($modelClass);
        $firstField = array_key_first($fields) ?? 'id';

        return [
            [
                'and' => [
                    [
                        'or' => [
                            ['0' => "<|{$firstField}|100"],
                            ['1' => "like|{$firstField}|%search%|0"],
                        ],
                    ],
                ],
            ],
            [
                'and' => [
                    [
                        'and' => [
                            ['0' => ">|{$firstField}|50"],
                            ['1' => "like|{$firstField}|%value%|0"],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function generateOrderByExamples(?string $modelClass): array|string
    {
        if ($modelClass) {
            $fields = $this->getModelFields($modelClass);
            $examples = [];
            $count = 0;
            foreach ($fields as $field => $type) {
                if ($count >= 2) {
                    break;
                }
                $direction = $count === 0 ? 'desc' : 'asc';
                $examples[] = "{\"{$field}\":\"{$direction}\"}";
                $count++;
            }
            if (!empty($examples)) {
                return $examples;
            }
        }
        return '[{"created_at":"desc"},{"iid":"asc"}]';
    }

    protected function getTableDescription(string $entity, string $module): string
    {
        $description = config("openapi-templates.entity_descriptions.{$entity}");

        if ($description) {
            return $description;
        }

        return "Manage {$this->getPlural($entity)} in the {$module} module.";
    }

    /**
     * Find the model class from an entity name
     *
     * ENHANCED: Better handling of global models (App\Models)
     *
     * Priority:
     * 1. Module-specific (Modules\{module}\Entities or Models)
     * 2. Global models (App\Models)
     *
     * @param string $entity Entity name
     * @param string $module Module name
     * @return string|null Fully-qualified model class name or null
     */
    protected function findModelClass(string $entity, string $module): ?string
    {
        $entityClassSingular = Str::studly(Str::singular($entity));
        $entityClassic = Str::studly($entity);
        // 1. Try in specified module first
        $modulePatterns = [
            "Modules\\{$module}\\Entities\\{$entityClassSingular}",
            "Modules\\{$module}\\Models\\{$entityClassSingular}",
            "Modules\\{$module}\\Entities\\{$entityClassic}",
            "Modules\\{$module}\\Models\\{$entityClassic}",
        ];

        foreach ($modulePatterns as $className) {
            if (class_exists($className)) {
                Log::channel('openapi')->debug('Model found in module', [
                    'entity' => $entity,
                    'module' => $module,
                    'class' => $className,
                ]);
                return $className;
            }
        }

        // 2. ⚠️ NEW: Try global models (App\Models)
        $globalModel = "App\\Models\\{$entityClassSingular}";
        if (class_exists($globalModel)) {
            Log::channel('openapi')->info('Using global model (not in module)', [
                'entity' => $entity,
                'module' => $module,
                'class' => $globalModel,
                'note' => 'Entity should be in General folder',
            ]);
            return $globalModel;
        }
        $globalModel = "App\\Models\\{$entityClassic}";
        if (class_exists($globalModel)) {
            Log::channel('openapi')->info('Using global model (not in module)', [
                'entity' => $entity,
                'module' => $module,
                'class' => $globalModel,
                'note' => 'Entity should be in General folder',
            ]);
            return $globalModel;
        }

        Log::channel('openapi')->warning('Model class not found anywhere', [
            'entity' => $entity,
            'module' => $module,
            'tried_module' => $modulePatterns,
            'tried_global' => $globalModel,
        ]);

        return null;
    }

    protected function findRuleClass(string $entity, string $module): ?string
    {
        $entityClass = Str::studly(Str::singular($entity));
        $className = "Modules\\{$module}\\Rules\\{$entityClass}Rule";

        $path = base_path(str_replace('\\', '/', $className) . '.php');

        if (file_exists($path)) {
            Log::channel('openapi')->debug('Rule class found (file exists)', [
                'entity' => $entity,
                'class' => $className,
                'path' => $path,
            ]);
            return $className;
        }

        try {
            if (class_exists($className, false)) {
                Log::channel('openapi')->debug('Rule class found (already loaded)', [
                    'entity' => $entity,
                    'class' => $className,
                ]);
                return $className;
            }
        } catch (\Throwable $e) {
            Log::channel('openapi')->debug('Rule class check failed', [
                'entity' => $entity,
                'class' => $className,
                'error' => $e->getMessage(),
            ]);
        }

        Log::channel('openapi')->debug('Rule class not found', [
            'entity' => $entity,
            'expected' => $className,
            'path_checked' => $path,
        ]);

        return null;
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }
}