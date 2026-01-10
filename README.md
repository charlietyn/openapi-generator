# Laravel OpenAPI Generator

[![Latest Version](https://img.shields.io/packagist/v/ronu/laravel-openapi-generator.svg?style=flat-square)](https://packagist.org/packages/ronu/laravel-openapi-generator)
[![Total Downloads](https://img.shields.io/packagist/dt/ronu/laravel-openapi-generator.svg?style=flat-square)](https://packagist.org/packages/ronu/laravel-openapi-generator)
[![License](https://img.shields.io/packagist/l/ronu/laravel-openapi-generator.svg?style=flat-square)](https://packagist.org/packages/ronu/laravel-openapi-generator)

**The ultimate automatic API documentation generator for Laravel applications**

Generate complete, production-ready API documentation in **three formats** with **99% automation**. No annotations, no manual work, just intelligent extraction from your existing Laravel code.

---

## 🎯 What Makes This Different?

Most documentation tools require you to annotate every controller method, every parameter, every response. This package takes a different approach:

- ✅ **Zero Annotations** - Automatically extracts from FormRequests, Models, Routes
- ✅ **Three Formats** - OpenAPI 3.0.3, Postman Collection v2.1, Insomnia Workspace v4
- ✅ **Smart Extraction** - 4-strategy cascade handles complex validation rules
- ✅ **Modular Architecture** - Perfect for Nwidart modularized Laravel apps
- ✅ **Battle-Tested** - From 20% accuracy to 95%+ in production

---

## 📋 Generated Output

### 1. OpenAPI 3.0.3 Specification
```yaml
openapi: 3.0.3
info:
  title: Your API
  version: 1.0.0
servers:
  - url: http://127.0.0.1:8000
    description: Artisan server
paths:
  /api/users:
    get:
      summary: List all users
      tags: [security, users]
      # ... complete spec with parameters, responses, examples
```

### 2. Postman Collection v2.1
- Complete request collection organized by modules
- Pre-configured environments (Artisan, Local, Production)
- Automatic test scripts for response validation
- Global variable tracking (`last_user_id`, etc.)

### 3. Insomnia Workspace v4
- Design-first workspace with full API spec
- Integrated environments with variable inheritance
- Request organization matching your module structure
- Bearer token management

---

## 🚀 Quick Start

### Installation

```bash
composer require ronu/laravel-openapi-generator
```

### Publish Configuration

```bash
php artisan vendor:publish --tag=openapi-config
php artisan vendor:publish --tag=openapi-templates
```

### Generate Documentation

```bash
# Generate all formats
php artisan openapi:generate --all

# Generate specific format
php artisan openapi:generate --with-postman
php artisan openapi:generate --with-insomnia

# Filter by API type
php artisan openapi:generate --all --api-type=api --api-type=mobile
```

### Access Documentation

```
GET /documentation/openapi.json
GET /documentation/openapi.yaml
GET /documentation/postman
GET /documentation/insomnia
```

---

## ✨ Key Features

### 1. **Intelligent Metadata Extraction**

Automatically extracts from:

- **Eloquent Models** - Field types, fillable attributes, relationships
- **FormRequests** - Validation rules with scenario detection
- **Routes** - HTTP methods, URIs, middleware, parameters
- **Custom Rules** - Even complex rules with database dependencies

**Example:**
```php
// Your FormRequest
class CreateUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'role_id' => ['required', Rule::exists('roles', 'id')],
        ];
    }
}

// Generated automatically:
{
  "name": "",           // string, required, max 255
  "email": "",          // email format, unique validation
  "role_id": 0          // integer, must exist in roles table
}
```

### 2. **4-Strategy Cascade for Complex Rules**

Handles validation rules with constructor dependencies:

```php
Rule::unique($this->connection . '.table', 'field')
```

**Extraction Strategies:**
1. ✅ Normal instantiation
2. ✅ Mock dependency injection via Reflection
3. ✅ Reflection without constructor invocation
4. ✅ File parsing as last resort

### 3. **Scenario-Based Documentation**

Different validation rules for different contexts:

```php
// Route definition
Route::post('/users', [UserController::class, 'store'])
    ->middleware('inject:_scenario=create');

Route::post('/users/bulk', [UserController::class, 'bulkCreate'])
    ->middleware('inject:_scenario=bulk_create');

// UserRules.php
public static function rules(): array
{
    return [
        'create' => [
            'name' => 'required|string',
            'email' => 'required|email',
        ],
        'bulk_create' => [
            'users' => 'required|array',
            'users.*.name' => 'required|string',
            'users.*.email' => 'required|email',
        ],
    ];
}
```

**Result:** Each endpoint gets the correct request body structure.

### 4. **GLOBAL vs LOCAL Variables**

Smart detection of parameter types:

```yaml
# GLOBAL variables (tracked across requests)
/api/users/{id}           → {{ _.last_user_id }}
/api/products/{id}        → {{ _.last_product_id }}

# LOCAL variables (endpoint-specific)
/api/users/search/{term}  → {{ _.term }}
```

**When to use GLOBAL:**
- Standard CRUD operations (show, update, delete)
- Parameter name is `id`
- Configured in `tracking_variables`

**When to use LOCAL:**
- Custom actions
- Non-ID parameters
- Query parameters

### 5. **Modular Architecture Support**

Perfect for Nwidart modules:

```
API Type (api, site, mobile)
  └─ Module (security, catalog, sales)
      └─ Entity (users, products, orders)
          └─ Actions (list, show, create, update, delete, custom)
```

**Example:**
```
[API] security.users.list
[SITE] catalog.products.show
[MOBILE] sales.orders.create
```

### 6. **Template-Based Customization**

**3-Level Documentation System:**

1. **Custom Endpoints** (highest priority)
   ```php
   // config/openapi-docs.php
   'custom_endpoints' => [
       'auth.login' => [
           'summary' => 'User authentication',
           'description' => 'Login with email and password',
           'request_example' => ['email' => '', 'password' => ''],
       ],
   ]
   ```

2. **Generic YAML Templates**
   ```yaml
   # storage/app/yaml-templates/generic_list.yaml
   summary: "List all {entity_plural}"
   description: "Retrieve paginated list of {entity_plural}"
   parameters:
     - name: page
       in: query
       schema: {type: integer}
   ```

3. **Automatic Fallback**
   - Always generates basic documentation from routes

### 7. **Automatic Test Generation**

Dynamic test scripts for Postman and Insomnia:

```javascript
// Generated for Postman
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has data", function () {
    pm.expect(pm.response.json()).to.have.property('data');
});

// Save ID for next requests
if (pm.response.json().data && pm.response.json().data.id) {
    pm.globals.set("last_user_id", pm.response.json().data.id);
}
```

**Configurable via:**
```php
// config/openapi-tests.php
'templates' => [
    'store' => [
        'status_201',
        'has_data',
        'save_to_global_var',
    ],
]
```

### 8. **Multi-Environment Support**

**Environment Hierarchy:**
```
Base Environment (variables + tracking)
  ├─ Artisan (http://127.0.0.1:8000)
  ├─ Local (http://localhost/project)
  └─ Production (https://project.com)
```

**Inheritance:**
- Child environments inherit from Base
- `tracking_variables` ONLY in Base
- Override variables in children

**Output:**
- **Postman:** 3 separate JSON files
- **Insomnia:** 4 environments in workspace (Base + 3 children)

---

## 📖 Documentation

- [Installation Guide](INSTALLATION.md) - Complete setup instructions
- [Implementation Guide](IMPLEMENTATION_GUIDE.md) - Deep dive for developers
- [Configuration Reference](#configuration)
- [Examples](#examples)

---

## ⚙️ Configuration

### Basic Configuration

```php
// config/openapi.php
return [
    'info' => [
        'title' => env('APP_NAME', 'Laravel API'),
        'version' => '1.0.0',
        'description' => 'Complete API documentation',
    ],
    
    'servers' => [
        ['url' => 'http://127.0.0.1:8000', 'description' => 'Artisan'],
        ['url' => 'https://api.example.com', 'description' => 'Production'],
    ],
    
    'api_types' => [
        'api' => 'API Admin',
        'site' => 'Public Website API',
        'mobile' => 'Mobile App API',
    ],
    
    'security' => [
        'bearer' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
        ],
    ],
];
```

### Entity Configuration

```php
// config/openapi-docs.php
'entities' => [
    'users' => [
        'singular' => 'user',
        'plural' => 'users',
        'model' => User::class,
        'description' => 'System users with role-based access',
    ],
],
```

### Custom Endpoint Documentation

```php
'custom_endpoints' => [
    'auth.login' => [
        'summary' => 'User login',
        'description' => 'Authenticate user and return JWT token',
        'request_example' => [
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ],
        'responses' => [
            200 => [
                'description' => 'Login successful',
                'example' => [
                    'token' => 'eyJ0eXAiOiJKV1QiLCJhbGc...',
                    'user' => ['id' => 1, 'name' => 'Admin'],
                ],
            ],
        ],
    ],
],
```

---

## 🎨 Examples

### Example 1: Basic CRUD Resource

```php
// Routes
Route::apiResource('users', UserController::class);

// Generated automatically:
// GET    /api/users           → security.users.list
// POST   /api/users           → security.users.create
// GET    /api/users/{id}      → security.users.show
// PUT    /api/users/{id}      → security.users.update
// DELETE /api/users/{id}      → security.users.delete
```

### Example 2: Custom Action with Scenario

```php
// Route
Route::post('/users/validate', [UserController::class, 'validate'])
    ->middleware('inject:_scenario=validate');

// UserRules.php
'validate' => [
    'email' => 'required|email|unique:users',
    'phone' => 'required|regex:/^\+?[1-9]\d{1,14}$/',
]

// Generated request body:
{
  "email": "",
  "phone": ""
}
```

### Example 3: Filtering by API Type

```bash
# Generate only for API admin
php artisan openapi:generate --all --api-type=api

# Generate for mobile and public site
php artisan openapi:generate --all --api-type=mobile --api-type=site
```

---

## 🏗️ Architecture

### Service Layer

```
OpenApiServices
├── Route Parsing & Analysis
├── Module/Entity Detection
├── Action Inference
└── Spec Building

MetadataExtractor
├── Model Introspection
├── FormRequest Extraction (4 strategies)
├── Relationship Detection
└── Field Type Mapping

DocumentationResolver
├── Custom Endpoint Lookup
├── YAML Template Rendering
├── Variable Replacement
└── Fallback Generation

PostmanCollectionGenerator
├── Collection v2.1 Structure
├── Request Organization
├── Test Script Injection
└── Environment Generation

InsomniaWorkspaceGenerator
├── Workspace v4 Structure
├── Resource Ordering
├── Environment Hierarchy
└── Authentication Config
```

---

## 🧪 Testing

```bash
# Run tests
composer test

# Run specific test suite
composer test -- --filter=OpenApiGeneration
composer test -- --filter=PostmanGeneration
composer test -- --filter=MetadataExtraction
```

---

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

---

## 📝 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

## 🙏 Credits

- Built with ❤️ for the Laravel community
- Inspired by real-world needs: reducing documentation from hours to minutes
- Special thanks to all contributors

---

## 🔮 Roadmap

- [ ] Swagger UI integration
- [ ] ReDoc theme support
- [ ] GraphQL endpoint detection
- [ ] Custom rule library expansion
- [ ] Performance optimizations with caching
- [ ] Multi-language support

---

## 💬 Support

- **Issues:** [GitHub Issues](https://github.com/ronu/laravel-openapi-generator/issues)
- **Discussions:** [GitHub Discussions](https://github.com/ronu/laravel-openapi-generator/discussions)
- **Email:** charlietyn@gmail.com

---

**Made with ☕ and late nights debugging Insomnia v4 vs v5 differences**
