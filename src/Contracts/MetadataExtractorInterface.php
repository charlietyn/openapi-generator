<?php

namespace Ronu\OpenApiGenerator\Contracts;

/**
 * Metadata Extractor Interface
 * 
 * Defines the contract for metadata extraction from different sources
 * (FormRequests, Models, Attributes, DocBlocks, Config, etc.)
 */
interface MetadataExtractorInterface
{
    /**
     * Extract metadata for an entity/action combination
     * 
     * @param string $entity Entity name (e.g., 'users', 'products')
     * @param string $module Module name (e.g., 'Security', 'Catalog')
     * @param string $action Action name (e.g., 'list', 'create', 'update')
     * @param mixed $route Route object or array with route information
     * @return array Extracted metadata array with keys like:
     *   - summary: string
     *   - description: string
     *   - validation_rules: array
     *   - query_params: array
     *   - body_params: array
     *   - responses: array
     *   - etc.
     */
    public function extract(
        string $entity,
        string $module,
        string $action,
        mixed $route
    ): array;
    
    /**
     * Get extraction priority
     * 
     * Higher priority extractors execute later and override lower priority ones
     * 
     * Priority levels:
     * 1 - Highest (explicit developer configuration - Attributes)
     * 2 - High (DocBlocks)
     * 3 - Medium (FormRequests with validation rules)
     * 4 - Low (Models with fillable/casts)
     * 5 - Lowest (Config fallback)
     * 
     * @return int Priority number (1-5, lower = higher priority)
     */
    public function getPriority(): int;
    
    /**
     * Check if this extractor can handle the given entity/action
     * 
     * Allows extractors to opt-out of extraction for certain routes
     * 
     * @param string $entity Entity name
     * @param string $module Module name
     * @param string $action Action name
     * @return bool True if can extract, false otherwise
     */
    public function canExtract(string $entity, string $module, string $action): bool;
}
