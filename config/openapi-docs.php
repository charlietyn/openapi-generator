<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CRUD Templates
    |--------------------------------------------------------------------------
    |
    | Generic documentation templates for standard CRUD operations.
    | These templates use placeholders that will be replaced with actual data:
    |
    | {entity_singular}  - Singular form of entity (e.g., "User")
    | {entity_plural}    - Plural form of entity (e.g., "Users")
    | {fields}           - Comma-separated list of model fields
    | {relations}        - Comma-separated list of model relations
    |
    */

    'crud_templates' => [
        'index' => [
            'summary' => 'List {entity_plural}',
            'description' => 'Retrieve a paginated list of {entity_plural} with optional filters and sorting. Available fields: {fields}',
            'responses' => [
                '200' => 'List retrieved successfully',
            ],
        ],

        'show' => [
            'summary' => 'Get {entity_singular}',
            'description' => 'Retrieve detailed information about a specific {entity_singular} including relations: {relations}',
            'responses' => [
                '200' => 'Resource retrieved successfully',
                '404' => 'Resource not found',
            ],
        ],

        'store' => [
            'summary' => 'Create {entity_singular}',
            'description' => 'Create a new {entity_singular} record. Required fields: {fields}. Related entities: {relations}',
            'responses' => [
                '201' => 'Resource created successfully',
                '422' => 'Validation error',
            ],
        ],

        'update' => [
            'summary' => 'Update {entity_singular}',
            'description' => 'Update an existing {entity_singular} record. Updatable fields: {fields}',
            'responses' => [
                '200' => 'Resource updated successfully',
                '404' => 'Resource not found',
                '422' => 'Validation error',
            ],
        ],

        'destroy' => [
            'summary' => 'Delete {entity_singular}',
            'description' => 'Permanently delete a {entity_singular} record. This action cannot be undone.',
            'responses' => [
                '200' => 'Resource deleted successfully',
                '204' => 'Resource deleted successfully',
                '404' => 'Resource not found',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Entities Metadata
    |--------------------------------------------------------------------------
    |
    | Define metadata for each entity in your application.
    | This helps generate better documentation automatically.
    |
    | Structure:
    | 'entity_key' => [
    |     'singular'    => 'Singular name',
    |     'plural'      => 'Plural name',
    |     'model'       => Model::class,
    |     'description' => 'Brief description of the entity',
    | ]
    |
    */

    'entities' => [
        // Security Module
        'users' => [
            'singular' => 'User',
            'plural' => 'Users',
            'model' => null,
            'description' => 'System users with role-based access control (RBAC)',
        ],

        'roles' => [
            'singular' => 'Role',
            'plural' => 'Roles',
            'model' => null, // Set your model class if exists
            'description' => 'User roles for permission management',
        ],

        'permissions' => [
            'singular' => 'Permission',
            'plural' => 'Permissions',
            'model' => null,
            'description' => 'Granular permissions for access control',
        ],

        // Catalog Module
        'products' => [
            'singular' => 'Product',
            'plural' => 'Products',
            'model' => null, // \App\Models\Product::class,
            'description' => 'Catalog products with categories, pricing, and inventory',
        ],

        'categories' => [
            'singular' => 'Category',
            'plural' => 'Categories',
            'model' => null,
            'description' => 'Product categories for organization',
        ],

        'brands' => [
            'singular' => 'Brand',
            'plural' => 'Brands',
            'model' => null,
            'description' => 'Product brands and manufacturers',
        ],

        // Sales Module
        'orders' => [
            'singular' => 'Order',
            'plural' => 'Orders',
            'model' => null,
            'description' => 'Customer orders with items, payment, and shipping',
        ],

        'customers' => [
            'singular' => 'Customer',
            'plural' => 'Customers',
            'model' => null,
            'description' => 'Customer information and purchase history',
        ],

        'invoices' => [
            'singular' => 'Invoice',
            'plural' => 'Invoices',
            'model' => null,
            'description' => 'Sales invoices and billing documents',
        ],

        // System Module
        'api-apps' => [
            'singular' => 'API Application',
            'plural' => 'API Applications',
            'model' => null,
            'description' => 'Third-party API applications and credentials',
        ],

        'settings' => [
            'singular' => 'Setting',
            'plural' => 'Settings',
            'model' => null,
            'description' => 'System configuration settings',
        ],

        /**
         * Add your custom entities here
         *
         * Example:
         * 'posts' => [
         *     'singular' => 'Post',
         *     'plural' => 'Posts',
         *     'model' => \App\Models\Post::class,
         *     'description' => 'Blog posts and articles',
         * ],
         */
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Endpoints Documentation
    |--------------------------------------------------------------------------
    |
    | Define documentation for non-CRUD endpoints (custom actions).
    |
    | Key format: 'entity.action' or 'controller.method'
    |
    | Example: 'api-apps.rotate' for POST /api-apps/{id}/rotate
    |
    */

    'custom_endpoints' => [
        'api-apps.rotate' => [
            'summary' => 'Rotate API Key',
            'description' => 'Generates a new API key for the application and invalidates the previous one. This action cannot be undone. Use with caution in production environments.',
            'request_fields' => [
                'reason' => 'Optional reason for key rotation (for audit logs)',
            ],
            'response_example' => [
                'new_key' => 'sk_live_abc123def456...',
                'old_key' => 'sk_live_xyz789uvw012...',
                'rotated_at' => '2025-12-24T10:00:00Z',
                'expires_at' => '2026-12-24T10:00:00Z',
            ],
        ],

        'users.restore' => [
            'summary' => 'Restore Deleted User',
            'description' => 'Restore a soft-deleted user account and all associated data.',
        ],

        'products.import' => [
            'summary' => 'Import Products from CSV',
            'description' => 'Bulk import products from a CSV file. Maximum file size: 10MB.',
            'request_fields' => [
                'file' => 'CSV file with product data',
                'skip_duplicates' => 'Skip existing products (boolean)',
            ],
        ],

        'orders.cancel' => [
            'summary' => 'Cancel Order',
            'description' => 'Cancel an order and process refund if payment was made.',
            'request_fields' => [
                'reason' => 'Cancellation reason',
                'refund' => 'Process refund (boolean)',
            ],
        ],

        /**
         * Add your custom endpoint documentation here
         *
         * Example:
         * 'posts.publish' => [
         *     'summary' => 'Publish Post',
         *     'description' => 'Publish a draft post and notify subscribers.',
         *     'request_fields' => [
         *         'schedule_at' => 'Optional scheduled publish date',
         *     ],
         * ],
         */
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Detection Settings
    |--------------------------------------------------------------------------
    |
    | Enable automatic detection of model fields and relations
    |
    */

    'auto_detect' => true,

    /*
    |--------------------------------------------------------------------------
    | Field Descriptions
    |--------------------------------------------------------------------------
    |
    | Common field descriptions to use in auto-generated documentation
    |
    */

    'field_descriptions' => [
        'id' => 'Unique identifier',
        'name' => 'Name',
        'email' => 'Email address',
        'password' => 'Password (hashed)',
        'description' => 'Description',
        'status' => 'Status (active, inactive, etc.)',
        'created_at' => 'Creation timestamp',
        'updated_at' => 'Last update timestamp',
        'deleted_at' => 'Deletion timestamp (soft delete)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation Priority
    |--------------------------------------------------------------------------
    |
    | Order of priority when resolving documentation:
    | 1. custom_endpoints (highest)
    | 2. PHPDoc in controller method
    | 3. crud_templates (fallback)
    |
    */

    'priority' => [
        'custom_endpoints',
        'phpdoc',
        'crud_templates',
    ],
];
