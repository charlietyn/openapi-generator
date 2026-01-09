<?php

namespace Ronu\OpenApiGenerator\Contracts;

/**
 * Example Generator Interface
 * 
 * Defines the contract for generating realistic examples
 */
interface ExampleGeneratorInterface
{
    /**
     * Generate realistic example value for a field
     * 
     * @param string $field Field name (e.g., 'email', 'phone', 'price')
     * @param string $type Field type (e.g., 'string', 'integer', 'boolean')
     * @param array $rules Validation rules for the field
     * @return mixed Generated example value
     */
    public function generateForField(string $field, string $type, array $rules = []): mixed;
    
    /**
     * Generate complete example from model
     * 
     * Uses model factory if available, otherwise generates from fillable fields
     * 
     * @param string|null $modelClass Fully qualified model class name
     * @return array Generated example as associative array
     */
    public function generateFromModel(?string $modelClass): array;
}
