<?php

namespace Ronu\OpenApiGenerator\Extractors;

use Ronu\OpenApiGenerator\Contracts\MetadataExtractorInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use ReflectionClass;

/**
 * Model Metadata Extractor
 * 
 * Extracts metadata from Eloquent Model classes
 */
class ModelExtractor implements MetadataExtractorInterface
{
    protected array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    public function getPriority(): int
    {
        return 4; // Low priority (fillable fallback)
    }
    
    public function canExtract(string $entity, string $module, string $action): bool
    {
        $modelClass = $this->findModelClass($entity, $module);
        return $modelClass !== null && class_exists($modelClass);
    }
    
    public function extract(string $entity, string $module, string $action, mixed $route): array
    {
        $modelClass = $this->findModelClass($entity, $module);
        if (!$modelClass || !class_exists($modelClass)) {
            return [];
        }
        
        try {
            $model = new $modelClass();
            
            $metadata = [
                'model_class' => $modelClass,
                'table_name' => $model->getTable(),
                'fillable_fields' => $model->getFillable(),
                'hidden_fields' => $model->getHidden(),
                'casts' => $model->getCasts(),
                'available_relations' => $this->extractRelations($modelClass),
                'uses_soft_delete' => $this->usesSoftDelete($modelClass),
            ];
            
            $this->log('info', 'Model metadata extracted', [
                'model' => class_basename($modelClass),
                'fillable_count' => count($metadata['fillable_fields']),
                'relations_count' => count($metadata['available_relations']),
            ]);
            
            return $metadata;
            
        } catch (\Throwable $e) {
            $this->log('warning', 'Model extraction failed', [
                'model' => class_basename($modelClass),
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
    
    protected function extractRelations(string $modelClass): array
    {
        $relations = [];
        $reflection = new ReflectionClass($modelClass);
        
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $modelClass || $method->getNumberOfParameters() > 0) {
                continue;
            }
            
            $returnType = $method->getReturnType();
            if (!$returnType) {
                continue;
            }
            
            $returnTypeName = $returnType->getName();
            if (str_contains($returnTypeName, 'Illuminate\Database\Eloquent\Relations')) {
                $relations[] = $method->getName();
            }
        }
        
        return $relations;
    }
    
    protected function usesSoftDelete(string $modelClass): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($modelClass));
    }
    
    protected function findModelClass(string $entity, string $module): ?string
    {
        $entityStudly = Str::studly(Str::singular($entity));
        
        // Try module-based model
        if ($this->config['modules']['enabled']) {
            $namespace = str_replace('{module}', $module, $this->config['modules']['namespace']);
            $class = "{$namespace}\\Entities\\{$entityStudly}";
            if (class_exists($class)) return $class;
            
            $class = "{$namespace}\\Models\\{$entityStudly}";
            if (class_exists($class)) return $class;
        }
        
        // Try app-based model
        $class = "App\\Models\\{$entityStudly}";
        if (class_exists($class)) return $class;
        
        return null;
    }
    
    protected function log(string $level, string $message, array $context = []): void
    {
        if ($this->config['logging']['enabled']) {
            Log::channel($this->config['logging']['channel'])
                ->{$level}('[OpenAPI ModelExtractor] ' . $message, $context);
        }
    }
}
