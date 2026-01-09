<?php

namespace Ronu\OpenApiGenerator\Contracts;

/**
 * Exporter Interface
 * 
 * Defines the contract for exporting documentation to different formats
 */
interface ExporterInterface
{
    /**
     * Export documentation to specific format
     * 
     * @param array $spec Complete OpenAPI specification
     * @param array $options Additional export options
     * @return string Exported content as string (JSON, YAML, etc.)
     */
    public function export(array $spec, array $options = []): string;
    
    /**
     * Get exporter name
     * 
     * @return string Exporter name (e.g., 'openapi', 'postman', 'insomnia')
     */
    public function getName(): string;
    
    /**
     * Get file extension
     * 
     * @return string File extension (e.g., 'json', 'yaml')
     */
    public function getExtension(): string;
}
