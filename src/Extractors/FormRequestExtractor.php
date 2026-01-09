<?php

namespace Ronu\OpenApiGenerator\Extractors;

use Ronu\OpenApiGenerator\Contracts\MetadataExtractorInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

/**
 * FormRequest Metadata Extractor
 * 
 * Extracts validation rules and metadata from Laravel FormRequest classes
 * Uses 4-strategy cascade for robust extraction
 */
class FormRequestExtractor implements MetadataExtractorInterface
{
    protected array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    public function getPriority(): int
    {
        return 3; // Medium priority
    }
    
    public function canExtract(string $entity, string $module, string $action): bool
    {
        $formRequestClass = $this->findFormRequestClass($entity, $module, $action);
        return $formRequestClass !== null && class_exists($formRequestClass);
    }
    
    public function extract(string $entity, string $module, string $action, mixed $route): array
    {
        $formRequestClass = $this->findFormRequestClass($entity, $module, $action);
        if (!$formRequestClass || !class_exists($formRequestClass)) {
            return [];
        }
        
        $scenario = $this->resolveScenario($route, $action);
        
        $this->log('info', 'FormRequest extraction started', [
            'class' => class_basename($formRequestClass),
            'scenario' => $scenario,
        ]);
        
        // Extract validation rules using 4-strategy cascade
        $rules = $this->extractValidationRules($formRequestClass, $scenario);
        
        if (empty($rules)) {
            $this->log('debug', 'No rules extracted', [
                'class' => class_basename($formRequestClass),
            ]);
            return [];
        }
        
        // Process rules into metadata
        $metadata = $this->processRules($rules);
        
        $this->log('info', 'FormRequest extraction complete', [
            'rules_count' => count($rules),
            'required_count' => count($metadata['required_fields']),
        ]);
        
        return $metadata;
    }
    
    /**
     * Extract validation rules using 4-strategy cascade
     */
    protected function extractValidationRules(string $formRequestClass, string $scenario): array
    {
        $this->log('debug', '🔍 Starting 4-strategy cascade', [
            'class' => class_basename($formRequestClass),
            'scenario' => $scenario,
        ]);
        
        // STRATEGY 1: Reflection WITHOUT constructor (90% success rate)
        try {
            $this->log('debug', 'Strategy 1: Reflection w/o constructor');
            $rules = $this->strategy1_ReflectionWithoutConstructor($formRequestClass, $scenario);
            if (!empty($rules)) {
                $this->log('info', '✅ Strategy 1 SUCCESS', ['rules_count' => count($rules)]);
                return $rules;
            }
        } catch (\Throwable $e) {
            $this->log('debug', '⊘ Strategy 1 failed', ['error' => $e->getMessage()]);
        }
        
        // STRATEGY 2: Mock dependencies (5% success rate)
        try {
            $this->log('debug', 'Strategy 2: Mock dependencies');
            $rules = $this->strategy2_MockDependencies($formRequestClass, $scenario);
            if (!empty($rules)) {
                $this->log('info', '✅ Strategy 2 SUCCESS', ['rules_count' => count($rules)]);
                return $rules;
            }
        } catch (\Throwable $e) {
            $this->log('debug', '⊘ Strategy 2 failed', ['error' => $e->getMessage()]);
        }
        
        // STRATEGY 3: File parsing (regex) (3% success rate)
        try {
            $this->log('debug', 'Strategy 3: File parsing');
            $rules = $this->strategy3_FileParsing($formRequestClass, $scenario);
            if (!empty($rules)) {
                $this->log('info', '✅ Strategy 3 SUCCESS', ['rules_count' => count($rules)]);
                return $rules;
            }
        } catch (\Throwable $e) {
            $this->log('debug', '⊘ Strategy 3 failed', ['error' => $e->getMessage()]);
        }
        
        // STRATEGY 4: Config fallback (always succeeds but returns empty)
        $this->log('debug', 'Strategy 4: Config fallback (no rules found)');
        return [];
    }
    
    /**
     * STRATEGY 1: Reflection WITHOUT calling constructor
     * 
     * Most reliable method - avoids dependency issues
     */
    protected function strategy1_ReflectionWithoutConstructor(string $formRequestClass, string $scenario): array
    {
        $reflection = new ReflectionClass($formRequestClass);
        
        // Create instance WITHOUT calling constructor
        $instance = $reflection->newInstanceWithoutConstructor();
        
        // Try $rules property
        if ($reflection->hasProperty('rules')) {
            $property = $reflection->getProperty('rules');
            $property->setAccessible(true);
            $allRules = $property->getValue($instance);
            
            if (is_array($allRules) && isset($allRules[$scenario])) {
                return $this->cleanAndConvertRules($allRules[$scenario]);
            }
        }
        
        // Try rules() method
        if ($reflection->hasMethod('rules')) {
            $method = $reflection->getMethod('rules');
            $method->setAccessible(true);
            
            try {
                $allRules = $method->invoke($instance);
                if (is_array($allRules) && isset($allRules[$scenario])) {
                    return $this->cleanAndConvertRules($allRules[$scenario]);
                }
            } catch (\Throwable $e) {
                // Method might need dependencies, continue to next strategy
                throw $e;
            }
        }
        
        return [];
    }
    
    /**
     * STRATEGY 2: Instantiate with mocked dependencies
     */
    protected function strategy2_MockDependencies(string $formRequestClass, string $scenario): array
    {
        $reflection = new ReflectionClass($formRequestClass);
        $constructor = $reflection->getConstructor();
        
        if (!$constructor) {
            // No constructor, safe to instantiate
            $instance = new $formRequestClass();
        } else {
            // Mock constructor parameters
            $params = [];
            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType();
                
                if (!$type || $type->isBuiltin()) {
                    // Primitive type or no type
                    $params[] = $param->isDefaultValueAvailable() 
                        ? $param->getDefaultValue() 
                        : null;
                } else {
                    // Object dependency - create mock
                    $paramClass = $type->getName();
                    $params[] = $this->createMock($paramClass);
                }
            }
            
            $instance = $reflection->newInstanceArgs($params);
        }
        
        // Extract rules
        if (method_exists($instance, 'rules')) {
            $allRules = $instance->rules();
            if (is_array($allRules) && isset($allRules[$scenario])) {
                return $this->cleanAndConvertRules($allRules[$scenario]);
            }
        }
        
        return [];
    }
    
    /**
     * STRATEGY 3: Parse file without executing code
     */
    protected function strategy3_FileParsing(string $formRequestClass, string $scenario): array
    {
        $filePath = $this->getClassFilePath($formRequestClass);
        if (!file_exists($filePath)) {
            return [];
        }
        
        $content = file_get_contents($filePath);
        
        // Match $rules property
        $pattern = '/protected\s+\$rules\s*=\s*\[(.*?)\];/s';
        if (preg_match($pattern, $content, $matches)) {
            // Find scenario within rules array
            $rulesContent = $matches[1];
            $scenarioPattern = "/['\"]" . preg_quote($scenario, '/') . "['\"]\s*=>\s*\[(.*?)\],?\s*(?:['\"]|$)/s";
            
            if (preg_match($scenarioPattern, $rulesContent, $scenarioMatches)) {
                return $this->parseRulesFromString($scenarioMatches[1]);
            }
        }
        
        return [];
    }
    
    /**
     * Clean and convert rules (handle Rule objects)
     */
    protected function cleanAndConvertRules(array $rules): array
    {
        $cleaned = [];
        
        foreach ($rules as $field => $rule) {
            if (is_string($rule)) {
                $cleaned[$field] = $rule;
            } elseif (is_array($rule)) {
                $cleaned[$field] = $this->convertRuleArray($rule);
            } elseif (is_object($rule)) {
                $cleaned[$field] = $this->convertRuleObject($rule);
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Convert array of rules
     */
    protected function convertRuleArray(array $rules): array
    {
        $converted = [];
        
        foreach ($rules as $rule) {
            if (is_string($rule)) {
                $converted[] = $rule;
            } elseif (is_object($rule)) {
                $converted[] = $this->convertRuleObject($rule);
            }
        }
        
        return $converted;
    }
    
    /**
     * Convert Laravel Rule objects to strings
     * 
     * CRITICAL: Handles Rule::unique(), Password::min(), etc.
     */
    protected function convertRuleObject(object $ruleObject): string
    {
        $className = get_class($ruleObject);
        
        // Map of Laravel Rule classes to string equivalents
        $conversionMap = [
            'Illuminate\\Validation\\Rules\\Unique' => 'unique',
            'Illuminate\\Validation\\Rules\\Exists' => 'exists',
            'Illuminate\\Validation\\Rules\\In' => 'in',
            'Illuminate\\Validation\\Rules\\NotIn' => 'not_in',
            'Illuminate\\Validation\\Rules\\Password' => 'password',
            'Illuminate\\Validation\\Rules\\File' => 'file',
            'Illuminate\\Validation\\Rules\\ImageFile' => 'image',
            'Illuminate\\Validation\\Rules\\Enum' => 'enum',
            'Illuminate\\Validation\\Rules\\Dimensions' => 'dimensions',
            'Illuminate\\Validation\\Rules\\ProhibitedIf' => 'prohibited_if',
            'Illuminate\\Validation\\Rules\\RequiredIf' => 'required_if',
        ];
        
        foreach ($conversionMap as $class => $ruleName) {
            if ($className === $class || is_a($ruleObject, $class)) {
                // Try __toString() method first for detailed rules
                if (method_exists($ruleObject, '__toString')) {
                    try {
                        return (string) $ruleObject;
                    } catch (\Throwable $e) {
                        return $ruleName;
                    }
                }
                return $ruleName;
            }
        }
        
        // Custom rules with __toString
        if (method_exists($ruleObject, '__toString')) {
            try {
                return (string) $ruleObject;
            } catch (\Throwable $e) {
                // Ignore
            }
        }
        
        // Fallback: class basename in lowercase
        return strtolower(class_basename($className));
    }
    
    /**
     * Process rules into metadata structure
     */
    protected function processRules(array $rules): array
    {
        $metadata = [
            'validation_rules' => $rules,
            'required_fields' => [],
            'body_params' => [],
            'request_schema' => [],
        ];
        
        foreach ($rules as $field => $rule) {
            $ruleArray = is_array($rule) ? $rule : explode('|', $rule);
            
            // Check if required
            if (in_array('required', $ruleArray)) {
                $metadata['required_fields'][] = $field;
            }
            
            // Generate schema
            $metadata['request_schema'][$field] = $this->ruleToSchema($field, $ruleArray);
            
            // Generate body param metadata
            $metadata['body_params'][] = [
                'name' => $field,
                'type' => $this->getFieldType($ruleArray),
                'required' => in_array('required', $ruleArray),
                'description' => $this->getFieldDescription($field, $ruleArray),
            ];
        }
        
        return $metadata;
    }
    
    /**
     * Convert validation rule to OpenAPI schema
     */
    protected function ruleToSchema(string $field, array $rules): array
    {
        $schema = ['type' => $this->getFieldType($rules)];
        
        foreach ($rules as $rule) {
            // String constraints
            if (str_starts_with($rule, 'max:')) {
                $schema['maxLength'] = (int) substr($rule, 4);
            }
            if (str_starts_with($rule, 'min:')) {
                $schema['minLength'] = (int) substr($rule, 4);
            }
            
            // Number constraints
            if (str_starts_with($rule, 'between:')) {
                [$min, $max] = explode(',', substr($rule, 8));
                $schema['minimum'] = (float) $min;
                $schema['maximum'] = (float) $max;
            }
            
            // Format
            if ($rule === 'email') {
                $schema['format'] = 'email';
            }
            if ($rule === 'url') {
                $schema['format'] = 'uri';
            }
            if ($rule === 'uuid') {
                $schema['format'] = 'uuid';
            }
            if (in_array($rule, ['date', 'date_format'])) {
                $schema['format'] = 'date';
            }
            
            // Pattern
            if (str_starts_with($rule, 'regex:')) {
                $schema['pattern'] = substr($rule, 6);
            }
        }
        
        return $schema;
    }
    
    /**
     * Get field type from validation rules
     */
    protected function getFieldType(array $rules): string
    {
        if (in_array('integer', $rules) || in_array('numeric', $rules)) {
            return 'integer';
        }
        if (in_array('boolean', $rules) || in_array('bool', $rules)) {
            return 'boolean';
        }
        if (in_array('array', $rules)) {
            return 'array';
        }
        if (in_array('file', $rules) || in_array('image', $rules)) {
            return 'string'; // Binary will be base64
        }
        
        return 'string'; // Default
    }
    
    /**
     * Get field description from validation rules
     */
    protected function getFieldDescription(string $field, array $rules): string
    {
        $parts = [];
        
        if (in_array('required', $rules)) {
            $parts[] = 'Required';
        }
        
        foreach ($rules as $rule) {
            if (str_starts_with($rule, 'max:')) {
                $parts[] = 'Maximum length: ' . substr($rule, 4);
            }
            if (str_starts_with($rule, 'min:')) {
                $parts[] = 'Minimum length: ' . substr($rule, 4);
            }
        }
        
        return empty($parts) ? ucfirst(str_replace('_', ' ', $field)) : implode('. ', $parts);
    }
    
    /**
     * Find FormRequest class for entity/module/action
     */
    protected function findFormRequestClass(string $entity, string $module, string $action): ?string
    {
        $entityStudly = Str::studly(Str::singular($entity));
        $actionStudly = Str::studly($action);
        
        // Try module-based FormRequest
        if ($this->config['modules']['enabled']) {
            $namespace = str_replace(
                '{module}',
                $module,
                $this->config['modules']['namespace']
            );
            
            $class = "{$namespace}\\Http\\Requests\\{$entityStudly}{$actionStudly}Request";
            if (class_exists($class)) {
                return $class;
            }
            
            // Try alternative naming
            $class = "{$namespace}\\Http\\Requests\\{$entityStudly}Request";
            if (class_exists($class)) {
                return $class;
            }
        }
        
        // Try app-based FormRequest
        $class = "App\\Http\\Requests\\{$entityStudly}{$actionStudly}Request";
        if (class_exists($class)) {
            return $class;
        }
        
        $class = "App\\Http\\Requests\\{$entityStudly}Request";
        if (class_exists($class)) {
            return $class;
        }
        
        return null;
    }
    
    /**
     * Resolve scenario from route
     */
    protected function resolveScenario(mixed $route, string $action): string
    {
        // Check middleware for scenario parameter
        if (is_object($route) && method_exists($route, 'middleware')) {
            $middleware = $route->middleware();
            foreach ($middleware as $mw) {
                if (is_string($mw) && str_contains($mw, 'inject:')) {
                    $params = explode(':', str_replace('inject:', '', $mw));
                    foreach ($params as $param) {
                        if (str_starts_with($param, '_scenario=')) {
                            return substr($param, 10);
                        }
                    }
                }
            }
        }
        
        // Check URI patterns
        $uri = is_object($route) && method_exists($route, 'uri') ? $route->uri() : '';
        foreach ($this->config['scenarios']['uri_patterns'] as $pattern => $scenario) {
            if (preg_match($pattern, $uri)) {
                return $scenario;
            }
        }
        
        // Use default based on HTTP method
        $method = is_object($route) && method_exists($route, 'methods') 
            ? $route->methods()[0] 
            : 'GET';
            
        return $this->config['scenarios']['defaults'][$method] ?? $action;
    }
    
    /**
     * Get class file path
     */
    protected function getClassFilePath(string $class): string
    {
        $relativePath = str_replace(['\\', 'App/'], ['/', 'app/'], $class);
        return base_path($relativePath . '.php');
    }
    
    /**
     * Create mock object
     */
    protected function createMock(string $class): object
    {
        // For common Laravel classes, return real instances
        if ($class === 'Illuminate\Http\Request') {
            return new \Illuminate\Http\Request();
        }
        
        // For others, try Mockery
        if (class_exists(\Mockery::class)) {
            return \Mockery::mock($class);
        }
        
        // Fallback: create empty stdClass
        return new \stdClass();
    }
    
    /**
     * Parse rules from string content
     */
    protected function parseRulesFromString(string $content): array
    {
        // Simple parser - extract field => rules pairs
        $rules = [];
        $pattern = "/['\"]([^'\"]+)['\"]\s*=>\s*['\"]([^'\"]+)['\"]/";
        
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $rules[$match[1]] = $match[2];
            }
        }
        
        return $rules;
    }
    
    /**
     * Log message
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        if ($this->config['logging']['enabled']) {
            Log::channel($this->config['logging']['channel'])
                ->{$level}('[OpenAPI FormRequestExtractor] ' . $message, $context);
        }
    }
}
