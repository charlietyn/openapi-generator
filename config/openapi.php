<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAPI Documentation Settings
    |--------------------------------------------------------------------------
    |
    | Configure the metadata and global settings for OpenAPI documentation
    |
    */

    'info' => [
        'title' => env('APP_NAME', 'Laravel API'),
        'description' => 'Complete API documentation for all application modules',
        'version' => env('API_VERSION', '1.0.0'),
        'contact' => [
            'name' => env('API_CONTACT_NAME', 'API Support'),
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
    | Define the available API servers for different environments
    |
    */

    'servers' => [
        [
            'url' => 'http://127.0.0.1:8000',
            'description' => 'Artisan server',
        ],
        [
            'url' => 'https://localhost/${{projectName}}/public',  // ← Placeholder
            'description' => 'Local Server',
        ],
        [
            'url' => 'https://${{projectName}}.com',  // ← Placeholder
            'description' => 'Production Server',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Schemes
    |--------------------------------------------------------------------------
    |
    | Define authentication methods available in your API
    |
    */

    'security' => [
        'BearerAuth' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
            'description' => 'JWT Bearer Token authentication',
        ],
        'ApiKeyAuth' => [
            'type' => 'apiKey',
            'in' => 'header',
            'name' => 'X-API-Key',
            'description' => 'API Key authentication',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Environments Configuration
    |--------------------------------------------------------------------------
    |
    | Define environments with hierarchical inheritance (Base → Sub-environments)
    |
    | IMPORTANT: tracking_variables should ONLY be in 'base' environment
    | These are GLOBAL variables used for chaining CRUD operations
    |
    */

    'environments' => [
        'base' => [
            'name' => 'Base Environment',
            'variables' => [
                'base_url' => env('APP_URL', 'http://localhost:8000'),
                'token' => '',
                'api_key' => '',
            ],

            /**
             * Tracking Variables (GLOBAL)
             *
             * These variables are used to chain CRUD operations across requests.
             * Add all resources that need ID tracking here.
             *
             * Format: 'last_{resource_name}_id' => ''
             *
             * Example: After creating a user, the ID is stored in 'last_user_id'
             * and can be used in subsequent requests like: /users/{{last_user_id}}
             */
            'tracking_variables' => [
                'last_user_id' => '',
                'last_role_id' => '',
                'last_permission_id' => '',
            ],
        ],

        'artisan' => [
            'name' => 'Artisan Environment',
            'parent' => 'base',
            'variables' => [
                'base_url' => 'http://127.0.0.1:8000',
                'api_key' => '__GENERATED__',
            ],
        ],

        'local' => [
            'name' => 'Local Environment',
            'parent' => 'base',
            'variables' => [
                'base_url' => 'http://localhost/${{projectName}}/public',
                'api_key' => '',
            ],
        ],

        'production' => [
            'name' => 'Production Environment',
            'parent' => 'base',
            'variables' => [
                'base_url' => 'http://${{projectName}}.com',
                'api_key' => '',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Types Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the different API types and their prefixes
    |
    */

    'api_types' => [
        'admin' => [
            'prefix' => 'admin',
            'file' => 'api.admin.php',
            'description' => 'Admin API - Backend management endpoints',
            'folder_name' => 'API Admin',
            'icon' => '🔐',
            'middleware' => ['api'],
            'enabled' => true,
        ],
        'site' => [
            'prefix' => 'site',
            'file' => 'api.frontend.php',
            'description' => 'Frontend Public API - Public facing endpoints',
            'folder_name' => 'API Frontend',
            'icon' => '🌐',
            'middleware' => ['api'],
            'enabled' => true,
        ],
        'mobile' => [
            'prefix' => 'mobile',
            'file' => 'api.mobile.php',
            'description' => 'Mobile API - Mobile application endpoints',
            'folder_name' => 'API Mobile',
            'icon' => '📱',
            'middleware' => ['api'],
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules Path
    |--------------------------------------------------------------------------
    |
    | Base path where your modules are located (if using modular structure)
    |
    */

    'modules_path' => base_path('Modules'),

    /*
    |--------------------------------------------------------------------------
    | Module Exclusions
    |--------------------------------------------------------------------------
    |
    | Exclude modules or their routes from documentation (Nwidart Modules)
    |
    */

    'exclude_modules' => [],
    'exclude_module_routes' => [],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Enable caching for generated OpenAPI documents
    |
    */

    'cache' => [
        'enabled' => env('OPENAPI_CACHE_ENABLED', true),
        'ttl' => env('OPENAPI_CACHE_TTL', 3600), // 1 hour
        'key_prefix' => 'openapi_spec_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Output Path
    |--------------------------------------------------------------------------
    |
    | Base directory for generated OpenAPI specs and collections.
    |
    */

    'output_path' => storage_path('app/public/openapi'),

    /*
   |--------------------------------------------------------------------------
   | Path Configurations
   |--------------------------------------------------------------------------
   |
   | Define paths to scan for models and request classes
   |
   */

    'paths' => [
        'models' => [
            'App\\Models',
            'Modules\\{module}\\Entities',
        ],
        'requests' => [
            'App\\Http\\Requests',
            'Modules\\{module}\\Http\\Requests',
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Route Filtering
    |--------------------------------------------------------------------------
    |
    | Exclude certain routes from documentation
    |
    */

    'exclude_routes' => [
        'api/documentation/*',
        'sanctum/*',
        '_ignition/*',
        'admin/modules',
        'admin/modules',
        'telescope/*',
        'horizon/*',
        /* web routes*/
        '*/create',        // GET /resource/create
        '*/{id}/edit',     // GET /resource/{id}/edit
        '*/{*}/edit',
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware to Security Mapping
    |--------------------------------------------------------------------------
    |
    | Map Laravel middleware to OpenAPI security requirements
    |
    */

    'middleware_security_map' => [
        'auth:sanctum' => ['BearerAuth'],
        'auth:api' => ['BearerAuth'],
        'api.key' => ['ApiKeyAuth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Examples
    |--------------------------------------------------------------------------
    |
    | Define default response examples for common status codes
    |
    */

    'response_examples' => [
        '200' => [
            'description' => 'Successful operation',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => ['type' => 'object'],
                        ],
                    ],
                ],
            ],
        ],
        '201' => [
            'description' => 'Resource created successfully',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => ['type' => 'object'],
                            'message' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ],
        '401' => [
            'description' => 'Unauthenticated',
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/Error',
                    ],
                    'example' => [
                        'message' => 'Unauthenticated.',
                    ],
                ],
            ],
        ],
        '403' => [
            'description' => 'Forbidden',
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/Error',
                    ],
                    'example' => [
                        'message' => 'This action is unauthorized.',
                    ],
                ],
            ],
        ],
        '404' => [
            'description' => 'Resource Not Found',
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/Error',
                    ],
                    'example' => [
                        'message' => 'Resource not found.',
                    ],
                ],
            ],
        ],
        '422' => [
            'description' => 'Validation Error',
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/ValidationError',
                    ],
                    'example' => [
                        'message' => 'The given data was invalid.',
                        'errors' => [
                            'email' => ['The email field is required.'],
                        ],
                    ],
                ],
            ],
        ],
        '500' => [
            'description' => 'Internal Server Error',
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/Error',
                    ],
                    'example' => [
                        'message' => 'Server Error',
                    ],
                ],
            ],
        ],
    ],
    /*
|--------------------------------------------------------------------------
| Routes Configuration (ADD THIS SECTION)
|--------------------------------------------------------------------------
|
| Configure HTTP routes for documentation access
|
*/
    'routes' => [
        'enabled' => env('OPENAPI_ROUTES_ENABLED', true),
        'prefix' => env('OPENAPI_ROUTES_PREFIX', 'documentation'),
        'middleware' => explode(',', env('OPENAPI_ROUTES_MIDDLEWARE', '')),
    ],
];
