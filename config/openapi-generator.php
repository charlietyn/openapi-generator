<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | API Information
    |--------------------------------------------------------------------------
    |
    | Basic information about your API that will appear in the documentation
    |
    */
    
    'info' => [
        'title' => env('APP_NAME', 'API Documentation'),
        'version' => '1.0.0',
        'description' => 'Complete API documentation for all application modules',
        'contact' => [
            'name' => 'API Support Team',
            'email' => env('API_CONTACT_EMAIL', 'support@example.com'),
            'url' => env('API_CONTACT_URL', 'https://example.com/support'),
        ],
        'license' => [
            'name' => 'MIT',
            'url' => 'https://opensource.org/licenses/MIT',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | API Servers
    |--------------------------------------------------------------------------
    |
    | Define different server environments for your API
    |
    */
    
    'servers' => [
        [
            'url' => env('API_URL_LOCAL', 'http://127.0.0.1:8000'),
            'description' => 'Local Development Server',
        ],
        [
            'url' => env('API_URL_STAGING', 'https://staging.example.com'),
            'description' => 'Staging Server',
        ],
        [
            'url' => env('API_URL_PRODUCTION', 'https://api.example.com'),
            'description' => 'Production Server',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | API Types
    |--------------------------------------------------------------------------
    |
    | Define different API types (separate API interfaces)
    | Each type can have its own prefix, title, and configuration
    |
    */
    
    'api_types' => [
        'api' => [
            'prefix' => 'api',
            'title' => 'Main API',
            'description' => 'Primary REST API for web and mobile applications',
            'enabled' => true,
        ],
        'mobile' => [
            'prefix' => 'mobile',
            'title' => 'Mobile API',
            'description' => 'Optimized API endpoints for mobile applications',
            'enabled' => true,
        ],
        'admin' => [
            'prefix' => 'admin',
            'title' => 'Admin API',
            'description' => 'Administrative interface endpoints',
            'enabled' => true,
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Security Schemes
    |--------------------------------------------------------------------------
    |
    | Define authentication/authorization schemes used in your API
    |
    */
    
    'security' => [
        'bearer' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
            'description' => 'JWT token authentication',
        ],
        'api_key' => [
            'type' => 'apiKey',
            'in' => 'header',
            'name' => 'X-API-Key',
            'description' => 'API Key authentication',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Middleware to Security Mapping
    |--------------------------------------------------------------------------
    |
    | Map Laravel middleware names to security schemes
    |
    */
    
    'middleware_security' => [
        'auth:sanctum' => ['bearer'],
        'auth:api' => ['bearer'],
        'api.key' => ['api_key'],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Template Paths
    |--------------------------------------------------------------------------
    |
    | Customize where templates are stored
    | Priority: custom > generic > auto-generated
    |
    */
    
    'templates' => [
        // Base path for all templates
        'path' => resource_path('openapi/templates'),
        
        // Custom templates for specific endpoints
        'custom' => resource_path('openapi/templates/custom'),
        
        // Generic reusable templates
        'generic' => resource_path('openapi/templates/generic'),
        
        // Template file extension
        'extension' => 'json',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Output Paths
    |--------------------------------------------------------------------------
    |
    | Where generated documentation files will be saved
    |
    */
    
    'output' => [
        'path' => storage_path('app/openapi'),
        
        'files' => [
            'openapi' => 'openapi.json',
            'openapi_yaml' => 'openapi.yaml',
            'postman' => 'postman-collection.json',
            'insomnia' => 'insomnia-workspace.json',
        ],
        
        // Create output directory if it doesn't exist
        'create_if_missing' => true,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Module Detection
    |--------------------------------------------------------------------------
    |
    | Configure how modules are detected (for Nwidart modules support)
    |
    */
    
    'modules' => [
        // Enable module detection
        'enabled' => true,
        
        // Base path for modules
        'path' => base_path('Modules'),
        
        // Module namespace pattern
        'namespace' => 'Modules\\{module}',
        
        // Fallback module for non-module routes
        'fallback' => 'General',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Route Filtering
    |--------------------------------------------------------------------------
    |
    | Configure which routes to include/exclude from documentation
    |
    */
    
    'routes' => [
        // Only include routes that match these prefixes
        'include_prefixes' => ['api', 'mobile', 'admin'],
        
        // Exclude routes that match these patterns
        'exclude_patterns' => [
            '*/telescope/*',
            '*/horizon/*',
            '*/_debugbar/*',
            '*/sanctum/csrf-cookie',
        ],
        
        // Exclude routes with these names
        'exclude_names' => [
            'debugbar.*',
            'telescope.*',
            'horizon.*',
        ],
        
        // Exclude routes that only hit the module root
        'exclude_module_root' => true,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Scenario Detection
    |--------------------------------------------------------------------------
    |
    | Configure how validation scenarios are detected from routes
    |
    */
    
    'scenarios' => [
        // Default scenarios for standard CRUD operations
        'defaults' => [
            'POST' => 'create',
            'PUT' => 'update',
            'PATCH' => 'update',
            'DELETE' => 'delete',
        ],
        
        // URI pattern to scenario mapping
        'uri_patterns' => [
            '/validate$' => 'create',
            '/bulk-create$' => 'bulk_create',
            '/bulk-update$' => 'bulk_update',
            '/bulk-delete$' => 'bulk_delete',
            '/import$' => 'import',
            '/export$' => 'export',
        ],
        
        // Middleware parameter extraction
        'middleware_param' => '_scenario',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Smart Description Generator
    |--------------------------------------------------------------------------
    |
    | Configure contextual description generation
    |
    */
    
    'descriptions' => [
        // Enable smart description generation
        'enabled' => true,
        
        // Default items per page for pagination
        'default_per_page' => 15,
        
        // Default sort field
        'default_sort_field' => 'created_at',
        
        // Default sort direction
        'default_sort_direction' => 'desc',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Example Generator
    |--------------------------------------------------------------------------
    |
    | Configure realistic example generation
    |
    */
    
    'examples' => [
        // Enable example generation
        'enabled' => true,
        
        // Try to use model factories for examples
        'use_factories' => true,
        
        // Try to use database records (will be sanitized)
        'use_database' => false,
        
        // Fields to always sanitize
        'sanitize_fields' => [
            'password',
            'password_hash',
            'token',
            'access_token',
            'refresh_token',
            'secret',
            'api_key',
            'private_key',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ],
        
        // Field name heuristics for realistic examples
        'heuristics' => true,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | Configure automatic validation of generated specs
    |
    */
    
    'validation' => [
        // Enable automatic validation
        'enabled' => true,
        
        // Fail if validation errors found
        'fail_on_errors' => false,
        
        // Warn if quality score below threshold
        'quality_threshold' => 85,
        
        // Validation rules
        'rules' => [
            'require_summaries' => true,
            'require_descriptions' => true,
            'require_examples' => true,
            'require_operation_ids' => true,
            'no_generic_descriptions' => true,
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Postman Export
    |--------------------------------------------------------------------------
    |
    | Configuration for Postman Collection export
    |
    */
    
    'postman' => [
        // Postman collection info
        'info' => [
            'name' => env('APP_NAME', 'API') . ' Collection',
            'description' => 'Generated Postman collection for ' . env('APP_NAME', 'API'),
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        ],
        
        // Generate tests for requests
        'generate_tests' => true,
        
        // Environment variables
        'environments' => [
            'local' => [
                'base_url' => 'http://127.0.0.1:8000',
                'token' => '',
                'api_key' => '',
            ],
            'staging' => [
                'base_url' => 'https://staging.example.com',
                'token' => '',
                'api_key' => '',
            ],
            'production' => [
                'base_url' => 'https://api.example.com',
                'token' => '',
                'api_key' => '',
            ],
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Insomnia Export
    |--------------------------------------------------------------------------
    |
    | Configuration for Insomnia Workspace export
    |
    */
    
    'insomnia' => [
        // Insomnia workspace info
        'workspace' => [
            'name' => env('APP_NAME', 'API') . ' Workspace',
            'description' => 'Generated Insomnia workspace for ' . env('APP_NAME', 'API'),
            'scope' => 'design',
        ],
        
        // Export format version
        'export_format' => 4,
        
        // Include environments
        'include_environments' => true,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Custom Extractors
    |--------------------------------------------------------------------------
    |
    | Register custom metadata extractors
    | They will be executed in the order specified
    |
    */
    
    'custom_extractors' => [
        // Example: App\OpenApi\Extractors\CustomExtractor::class,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Custom Generators
    |--------------------------------------------------------------------------
    |
    | Override default generators with custom implementations
    |
    */
    
    'generators' => [
        'description' => \Ronu\OpenApiGenerator\Generators\SmartDescriptionGenerator::class,
        'example' => \Ronu\OpenApiGenerator\Generators\RealisticExampleGenerator::class,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Response Examples
    |--------------------------------------------------------------------------
    |
    | Default response examples by status code
    |
    */
    
    'responses' => [
        '200' => [
            'description' => 'Successful operation',
        ],
        '201' => [
            'description' => 'Resource created successfully',
        ],
        '204' => [
            'description' => 'Resource deleted successfully',
        ],
        '400' => [
            'description' => 'Bad request - Invalid input',
        ],
        '401' => [
            'description' => 'Unauthorized - Authentication required',
        ],
        '403' => [
            'description' => 'Forbidden - Insufficient permissions',
        ],
        '404' => [
            'description' => 'Resource not found',
        ],
        '422' => [
            'description' => 'Validation error',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string'],
                            'errors' => ['type' => 'object'],
                        ],
                    ],
                ],
            ],
        ],
        '500' => [
            'description' => 'Internal server error',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging behavior
    |
    */
    
    'logging' => [
        // Enable detailed logging
        'enabled' => env('OPENAPI_LOGGING', false),
        
        // Log channel
        'channel' => env('OPENAPI_LOG_CHANNEL', 'stack'),
        
        // Log level
        'level' => env('OPENAPI_LOG_LEVEL', 'debug'),
    ],
    
];
