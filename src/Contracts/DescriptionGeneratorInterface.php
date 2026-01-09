<?php

namespace Ronu\OpenApiGenerator\Contracts;

/**
 * Description Generator Interface
 * 
 * Defines the contract for generating contextual descriptions
 */
interface DescriptionGeneratorInterface
{
    /**
     * Generate contextual description for an endpoint
     * 
     * @param string $entity Entity name (e.g., 'users')
     * @param string $action Action name (e.g., 'list', 'show', 'create')
     * @param array $metadata Complete metadata array with:
     *   - query_params: array
     *   - available_relations: array
     *   - fillable_fields: array
     *   - required_fields: array
     *   - uses_soft_delete: bool
     *   - etc.
     * @return string Generated description
     */
    public function generate(string $entity, string $action, array $metadata): string;
}
