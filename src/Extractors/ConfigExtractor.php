<?php

namespace Ronu\OpenApiGenerator\Extractors;

use Ronu\OpenApiGenerator\Contracts\MetadataExtractorInterface;

/**
 * Config Metadata Extractor
 * 
 * Fallback extractor - always succeeds but returns minimal data
 */
class ConfigExtractor implements MetadataExtractorInterface
{
    protected array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    public function getPriority(): int
    {
        return 5; // Lowest priority (fallback)
    }
    
    public function canExtract(string $entity, string $module, string $action): bool
    {
        return true; // Always can extract (fallback)
    }
    
    public function extract(string $entity, string $module, string $action, mixed $route): array
    {
        // Return minimal metadata
        return [
            'entity' => $entity,
            'module' => $module,
            'action' => $action,
        ];
    }
}
