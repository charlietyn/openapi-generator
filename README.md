# 📚 Laravel OpenAPI Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/your-vendor/openapi-generator.svg?style=flat-square)](https://packagist.org/packages/your-vendor/openapi-generator)
[![Total Downloads](https://img.shields.io/packagist/dt/your-vendor/openapi-generator.svg?style=flat-square)](https://packagist.org/packages/your-vendor/openapi-generator)
[![License](https://img.shields.io/packagist/l/your-vendor/openapi-generator.svg?style=flat-square)](https://packagist.org/packages/your-vendor/openapi-generator)

Automatic OpenAPI 3.0.3 documentation generator for Laravel with **99% automation** from FormRequests and Eloquent Models.

## ✨ Features

- 🚀 **99% Automatic** - Extracts from FormRequests and Models
- 📝 **Multi-Format Export** - OpenAPI, Postman Collection, Insomnia Workspace
- 🎯 **Smart Generation** - Contextual descriptions and realistic examples
- ✅ **Quality Validation** - Built-in spec validation with quality metrics
- 🔧 **Highly Configurable** - Customize via templates and config
- 🏗️ **Modular Architecture** - Support for Nwidart modules
- 🎨 **Template System** - JSON templates for custom endpoints
- 📊 **Multiple API Types** - Support for api, mobile, admin, etc.

## 📋 Requirements

- PHP 8.1+
- Laravel 10.x, 11.x, or 12.x
- Optional: Nwidart Laravel Modules

## 🚀 Installation

Install via Composer:

```bash
composer require your-vendor/openapi-generator
```

Publish configuration and resources:

```bash
php artisan vendor:publish --provider="YourVendor\OpenApiGenerator\OpenApiGeneratorServiceProvider"
```

This will create:
- `config/openapi-generator.php` - Main configuration
- `resources/openapi/templates/` - Template directory for customization

## ⚙️ Configuration

### Basic Configuration

Edit `config/openapi-generator.php`:

```php
return [
    // API Information
    'info' => [
        'title' => env('APP_NAME', 'API Documentation'),
        'version' => '1.0.0',
        'description' => 'Complete API documentation',
    ],
    
    // API Types (separate API interfaces)
    'api_types' => [
        'api' => ['prefix' => 'api', 'title' => 'Main API'],
        'mobile' => ['prefix' => 'mobile', 'title' => 'Mobile API'],
        'admin' => ['prefix' => 'admin', 'title' => 'Admin API'],
    ],
    
    // Template locations
    'templates' => [
        'path' => resource_path('openapi/templates'),
        'custom' => resource_path('openapi/templates/custom'),
        'generic' => resource_path('openapi/templates/generic'),
    ],
];
```

### Template System

Create custom templates in `resources/openapi/templates/`:

**Structure:**
```
resources/openapi/templates/
├── custom/                    # Specific endpoints
│   ├── auth.login.json       # Custom login endpoint
│   └── users.create.json     # Custom user creation
└── generic/                   # Reusable action templates
    ├── list.json             # Generic list template
    ├── show.json             # Generic show template
    ├── create.json           # Generic create template
    ├── update.json           # Generic update template
    └── delete.json           # Generic delete template
```

**Example Custom Template** (`resources/openapi/templates/custom/auth.login.json`):

```json
{
  "summary": "User login",
  "description": "Authenticate user and receive access token",
  "operationId": "auth.login",
  "tags": ["Authentication"],
  "requestBody": {
    "required": true,
    "content": {
      "application/json": {
        "schema": {
          "type": "object",
          "required": ["email", "password"],
          "properties": {
            "email": {
              "type": "string",
              "format": "email",
              "example": "admin@example.com"
            },
            "password": {
              "type": "string",
              "format": "password",
              "example": "password123"
            }
          }
        }
      }
    }
  },
  "responses": {
    "200": {
      "description": "Login successful",
      "content": {
        "application/json": {
          "schema": {
            "type": "object",
            "properties": {
              "token": {"type": "string"},
              "user": {"type": "object"}
            }
          }
        }
      }
    },
    "401": {
      "description": "Invalid credentials"
    }
  }
}
```

**Generic Template with Variables** (`resources/openapi/templates/generic/list.json`):

```json
{
  "summary": "List {{entity_plural}}",
  "description": "{{smart_description}}",
  "operationId": "{{module}}.{{entity}}.list",
  "tags": ["{{module_title}}"],
  "parameters": [
    {
      "name": "page",
      "in": "query",
      "schema": {"type": "integer", "default": 1}
    },
    {
      "name": "per_page",
      "in": "query",
      "schema": {"type": "integer", "default": 15}
    }
  ],
  "responses": {
    "200": {
      "description": "Successful operation",
      "content": {
        "application/json": {
          "schema": {
            "type": "object",
            "properties": {
              "data": {
                "type": "array",
                "items": {"$ref": "#/components/schemas/{{entity_singular}}"}
              },
              "meta": {"$ref": "#/components/schemas/PaginationMeta"}
            }
          }
        }
      }
    }
  }
}
```

## 🎯 Usage

### Basic Usage

Generate all documentation:

```bash
php artisan openapi:generate
```

### Advanced Usage

**Generate specific API type:**
```bash
php artisan openapi:generate --api-type=api
php artisan openapi:generate --api-type=mobile,admin
```

**Generate specific format:**
```bash
php artisan openapi:generate --format=openapi
php artisan openapi:generate --format=postman
php artisan openapi:generate --format=insomnia
php artisan openapi:generate --format=all
```

**With validation:**
```bash
php artisan openapi:generate --validate
```

**With custom output path:**
```bash
php artisan openapi:generate --output=public/docs
```

**Verbose output:**
```bash
php artisan openapi:generate -v
```

## 🏗️ How It Works

### 1. Automatic Extraction

The package automatically extracts metadata from:

**FormRequests:**
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UserStoreRequest extends FormRequest
{
    protected $rules = [
        'create' => [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => ['required', Password::min(8)],
            'role_id' => 'required|exists:roles,id',
        ],
    ];
}
```

**Models:**
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    
    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
}
```

### 2. Smart Generation

The package generates:

**Contextual Descriptions:**
```
Before: "Retrieve paginated list"
After:  "Retrieve a paginated collection of User resources with optional 
         filtering via: role, status. Supports eager loading of: role, permissions."
```

**Realistic Examples:**
```json
Before: {"name": "architecto", "email": "example@example.com"}
After:  {"name": "John Doe", "email": "john.doe@example.com", "password": "SecureP@ss123"}
```

### 3. Template Priority System

1. **Custom Templates** (Highest) - `resources/openapi/templates/custom/entity.action.json`
2. **Generic Templates** (Medium) - `resources/openapi/templates/generic/action.json`
3. **Auto-Generated** (Lowest) - From FormRequest + Model metadata

### 4. Quality Validation

Automatic validation with quality metrics:

```
✅ Structural validation: PASSED
📊 Quality Metrics:
  - Summary coverage: 100%
  - Description coverage: 98.7%
  - Example coverage: 94.2%
  - Quality Score: 94.5/100 ⭐
```

## 📖 Template Variables

### Available Variables

Use these variables in your templates:

| Variable | Description | Example |
|----------|-------------|---------|
| `{{entity}}` | Entity name (singular) | `user` |
| `{{entity_plural}}` | Entity name (plural) | `users` |
| `{{entity_singular}}` | Entity name (singular, title) | `User` |
| `{{module}}` | Module name | `Security` |
| `{{module_title}}` | Module title | `Security` |
| `{{action}}` | Action name | `create` |
| `{{smart_description}}` | Auto-generated description | `Submit a new...` |
| `{{request_schema}}` | Request body schema | `{...}` |
| `{{response_schema}}` | Response schema | `{...}` |
| `{{request_example}}` | Request example | `{...}` |
| `{{response_example}}` | Response example | `{...}` |

### Conditional Blocks

```json
{
  "{{#if has_relations}}",
  "x-relations": {{relations}},
  "{{/if}}"
}
```

## 🔧 Advanced Configuration

### Custom Extractors

Create your own metadata extractor:

```php
namespace App\OpenApi\Extractors;

use YourVendor\OpenApiGenerator\Contracts\MetadataExtractorInterface;

class CustomExtractor implements MetadataExtractorInterface
{
    public function getPriority(): int
    {
        return 1; // Higher number = higher priority
    }
    
    public function canExtract(string $entity, string $module, string $action): bool
    {
        return true; // Your logic
    }
    
    public function extract(string $entity, string $module, string $action, $route): array
    {
        return [
            'summary' => 'Custom summary',
            'description' => 'Custom description',
            // ... more metadata
        ];
    }
}
```

Register in `config/openapi-generator.php`:

```php
'custom_extractors' => [
    App\OpenApi\Extractors\CustomExtractor::class,
],
```

### Custom Generators

Create custom description generator:

```php
namespace App\OpenApi\Generators;

use YourVendor\OpenApiGenerator\Contracts\DescriptionGeneratorInterface;

class CustomDescriptionGenerator implements DescriptionGeneratorInterface
{
    public function generate(string $entity, string $action, array $metadata): string
    {
        return "Your custom description for {$entity} {$action}";
    }
}
```

Register in config:

```php
'generators' => [
    'description' => App\OpenApi\Generators\CustomDescriptionGenerator::class,
],
```

## 🎨 Output Formats

### OpenAPI 3.0.3

```json
{
  "openapi": "3.0.3",
  "info": {...},
  "servers": [...],
  "paths": {...},
  "components": {...}
}
```

### Postman Collection v2.1

```json
{
  "info": {...},
  "item": [...],
  "auth": {...},
  "variable": [...]
}
```

### Insomnia Workspace v4

```json
{
  "_type": "export",
  "__export_format": 4,
  "resources": [...]
}
```

## 🧪 Testing

Run tests:

```bash
composer test
```

Run with coverage:

```bash
composer test-coverage
```

Static analysis:

```bash
composer analyse
```

## 📚 Documentation

Full documentation available at: [https://your-docs-site.com](https://your-docs-site.com)

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 🔒 Security

If you discover any security issues, please email security@example.com instead of using the issue tracker.

## 📝 Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for recent changes.

## 📄 License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

## 🙏 Credits

- [Scribe](https://scribe.knuckles.wtf) - Inspiration for reflection without constructor
- [Speakeasy](https://www.speakeasy.com) - Guidelines for contextual descriptions
- [cebe/php-openapi](https://github.com/cebe/php-openapi) - OpenAPI validation

## 💡 Examples

Check the [examples](examples/) directory for:
- Sample templates
- Custom extractors
- Integration examples
- Advanced configurations

---

Made with ❤️ for the Laravel community
