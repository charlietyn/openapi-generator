<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Template System Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the JSON-based template documentation system.
    | This system generates OpenAPI documentation automatically from templates.
    |
    */

    /**
     * Enable template system
     *
     * Set to false to disable template-based documentation and use fallback
     */
    'enabled' => env('OPENAPI_TEMPLATES_ENABLED', true),

    /**
     * Template paths
     *
     * Define where templates are located
     */
    'paths' => [
        'generic' => resource_path('openapi/templates/generic'),
        'custom' => resource_path('openapi/templates/custom'),
    ],

    /**
     * Generic template mapping
     *
     * Maps action names to template files (supports aliases)
     */
    'generic_templates' => [
        // Standard CRUD operations
        'list' => 'list.json',
        'index' => 'list.json',  // Alias for list

        'show' => 'show.json',
        'view' => 'show.json',   // Alias for show

        'create' => 'create.json',
        'store' => 'create.json', // Alias for create

        'update' => 'update.json',
        'edit' => 'update.json',  // Alias for update

        'delete' => 'delete.json',
        'destroy' => 'delete.json', // Alias for delete

        /**
         * Add your custom action mappings here
         *
         * Example:
         * 'export' => 'export.json',
         * 'import' => 'import.json',
         */
    ],

    /**
     * Query builder documentation
     *
     * Configure what to document for LIST endpoints
     */
    'query_builder' => [
        'document_operators' => true,
        'document_relations' => true,
        'document_pagination' => true,
        'document_sorting' => true,
        'generate_examples' => true,
    ],

    /**
     * Model introspection
     *
     * Enable automatic extraction of metadata from models
     */
    'auto_detect' => [
        'relations' => true,    // Extract model relations
        'validations' => true,  // Extract validation rules from Rule classes
        'fields' => true,       // Extract fillable fields
        'casts' => true,        // Extract field casts
    ],

    /**
     * Entity descriptions
     *
     * Optional manual descriptions for entities (overrides auto-generated)
     *
     * Format: 'entity-name' => 'Description'
     */
    'entity_descriptions' => [
        'system-logs' => 'System activity and audit logs for security monitoring',
        'users' => 'User accounts and authentication management',
        'roles' => 'Role-based access control (RBAC) definitions',
        'permissions' => 'Granular permission management for access control',

        /**
         * Add your entity descriptions here
         *
         * Example:
         * 'products' => 'Product catalog with pricing and inventory',
         * 'orders' => 'Customer orders with payment and shipping tracking',
         */
    ],

    /**
     * Template rendering options
     */
    'rendering' => [
        // Enable debug mode (logs all variable interpolations)
        'debug' => env('OPENAPI_TEMPLATES_DEBUG', false),

        // Validate output against OpenAPI schema
        'validate_output' => env('OPENAPI_TEMPLATES_VALIDATE', false),

        // Cache rendered templates
        'cache_enabled' => env('OPENAPI_TEMPLATES_CACHE', true),

        // Cache TTL in seconds
        'cache_ttl' => env('OPENAPI_TEMPLATE_CACHE_TTL', 3600),
    ],

    /**
     * Custom filters for template renderer
     *
     * You can register custom filters here
     *
     * Example:
     * 'filters' => [
     *     'currency' => function($value) {
     *         return '$' . number_format($value, 2);
     *     },
     * ],
     */
    'filters' => [
        // Custom filters will be registered here
    ],

    /**
     * Validation configuration
     */
    'validation' => [
        // Require templates to have 'summary' field
        'require_summary' => true,

        // Require templates to have 'description' field
        'require_description' => true,

        // Warn about missing variables (not an error)
        'warn_missing_variables' => true,
    ],

    /**
     * Examples configuration
     */
    'examples' => [
        // Maximum number of example values to generate
        'max_attr_examples' => 3,
        'max_oper_examples' => 2,
        'max_orderby_examples' => 2,

        // Auto-generate realistic examples
        'realistic_examples' => true,
    ],

    /**
     * Performance configuration
     */
    'performance' => [
        // Maximum depth for relation introspection
        'max_relation_depth' => 3,

        // Cache metadata extraction results
        'cache_metadata' => true,

        // Metadata cache TTL (seconds)
        'metadata_cache_ttl' => 3600,
    ],
];
