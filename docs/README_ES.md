# Laravel OpenAPI Generator - Documentacion Completa

## Guia Avanzada para Desarrolladores Laravel

**Version**: 1.2.5
**Compatibilidad**: Laravel 10.x, 11.x, 12.x
**PHP**: 8.1+

---

## Tabla de Contenidos

1. [Introduccion](#introduccion)
2. [Instalacion](#instalacion)
3. [Configuracion](#configuracion)
4. [Comandos Artisan](#comandos-artisan)
5. [Endpoints HTTP](#endpoints-http)
6. [Sistema de Plantillas](#sistema-de-plantillas)
7. [Extraccion de Metadatos](#extraccion-de-metadatos)
8. [Generacion de Tests](#generacion-de-tests)
9. [Uso Programatico](#uso-programatico)
10. [Arquitectura Modular](#arquitectura-modular)
11. [Casos de Uso Avanzados](#casos-de-uso-avanzados)
12. [Troubleshooting](#troubleshooting)

---

## Introduccion

Laravel OpenAPI Generator es una biblioteca que automatiza la generacion de documentacion API a partir de tu codigo Laravel existente. Genera especificaciones en:

- **OpenAPI 3.0.3** (JSON y YAML)
- **Postman Collection v2.1**
- **Insomnia Workspace v4**

### Caracteristicas Principales

- Extraccion automatica de reglas de validacion desde FormRequests
- Deteccion inteligente de relaciones Eloquent
- Soporte para multiples tipos de API (api, site, mobile, admin)
- Sistema de plantillas personalizable
- Generacion automatica de tests
- Cache configurable para optimizar rendimiento
- Soporte nativo para arquitecturas modulares (Nwidart)

---

## Instalacion

### Paso 1: Instalar via Composer

```bash
composer require ronu/laravel-openapi-generator
```

### Paso 2: Publicar Configuraciones

```bash
# Publicar todas las configuraciones
php artisan vendor:publish --tag=openapi-config

# Esto publica:
# - config/openapi.php
# - config/openapi-docs.php
# - config/openapi-tests.php
# - config/openapi-templates.php
```

### Paso 3: Publicar Plantillas (Opcional)

```bash
php artisan vendor:publish --tag=openapi-templates

# Esto crea:
# - resources/openapi/templates/generic/
# - resources/openapi/templates/custom/
```

### Paso 4: Verificar Instalacion

```bash
php artisan openapi:generate --help
```

---

## Configuracion

### Archivo Principal: `config/openapi.php`

#### Informacion de la API

```php
'info' => [
    'title' => env('APP_NAME', 'Mi API Laravel'),
    'description' => 'API RESTful completa para mi aplicacion',
    'version' => '1.0.0',
    'contact' => [
        'name' => 'Equipo de Desarrollo',
        'email' => 'dev@miempresa.com',
        'url' => 'https://miempresa.com/soporte',
    ],
    'license' => [
        'name' => 'MIT',
        'url' => 'https://opensource.org/licenses/MIT',
    ],
],
```

#### Configuracion de Servidores

```php
'servers' => [
    [
        'url' => 'http://127.0.0.1:8000',
        'description' => 'Servidor de Desarrollo (Artisan)',
    ],
    [
        'url' => 'https://localhost/${{projectName}}/public',
        'description' => 'Servidor Local (XAMPP/WAMP)',
    ],
    [
        'url' => 'https://api.${{projectName}}.com',
        'description' => 'Servidor de Produccion',
    ],
],
```

> **Nota**: `${{projectName}}` se reemplaza automaticamente con el nombre del proyecto.

#### Tipos de API

```php
'api_types' => [
    'api' => [
        'prefix' => 'api',
        'folder_name' => 'API General',
        'enabled' => true,
    ],
    'admin' => [
        'prefix' => 'admin',
        'folder_name' => 'API Administracion',
        'enabled' => true,
    ],
    'site' => [
        'prefix' => 'site',
        'folder_name' => 'API Frontend',
        'enabled' => true,
    ],
    'mobile' => [
        'prefix' => 'mobile',
        'folder_name' => 'API Mobile',
        'enabled' => true,
    ],
],
```

#### Esquemas de Seguridad

```php
'security' => [
    'BearerAuth' => [
        'type' => 'http',
        'scheme' => 'bearer',
        'bearerFormat' => 'JWT',
        'description' => 'Autenticacion mediante token JWT',
    ],
    'ApiKeyAuth' => [
        'type' => 'apiKey',
        'in' => 'header',
        'name' => 'X-API-Key',
        'description' => 'Autenticacion mediante API Key',
    ],
    'BasicAuth' => [
        'type' => 'http',
        'scheme' => 'basic',
        'description' => 'Autenticacion HTTP Basic',
    ],
],
```

#### Mapeo de Middleware a Seguridad

```php
'middleware_security_map' => [
    'auth:sanctum' => ['BearerAuth'],
    'auth:api' => ['BearerAuth'],
    'auth' => ['BearerAuth'],
    'api.key' => ['ApiKeyAuth'],
    'basic.auth' => ['BasicAuth'],
],
```

#### Configuracion de Cache

```php
'cache' => [
    'enabled' => true,           // Activar/desactivar cache
    'ttl' => 3600,               // Tiempo de vida en segundos (1 hora)
    'key_prefix' => 'openapi_',  // Prefijo para claves de cache
],
```

#### Exclusion de Rutas

```php
'exclude_routes' => [
    'api/documentation/*',    // Rutas de documentacion
    'sanctum/*',              // Rutas de Sanctum
    '*/create',               // Formularios de creacion
    '*/{id}/edit',            // Formularios de edicion
    '_ignition/*',            // Debugger
    '_debugbar/*',            // Debug bar
    'horizon/*',              // Laravel Horizon
    'telescope/*',            // Laravel Telescope
],
```

#### Ruta de Salida

```php
'output_path' => storage_path('app/public/openapi'),
// Los archivos se generaran en: storage/app/public/openapi/
```

---

### Archivo de Documentacion: `config/openapi-docs.php`

#### Plantillas CRUD

```php
'crud_templates' => [
    'index' => [
        'summary' => 'Listar __VAR:entity_plural__',
        'description' => 'Obtiene una lista paginada de __VAR:entity_plural__',
        'tags' => ['__VAR:module__'],
    ],
    'show' => [
        'summary' => 'Obtener __VAR:entity_singular__',
        'description' => 'Obtiene los detalles de un/a __VAR:entity_singular__',
    ],
    'store' => [
        'summary' => 'Crear __VAR:entity_singular__',
        'description' => 'Crea un nuevo/a __VAR:entity_singular__',
    ],
    'update' => [
        'summary' => 'Actualizar __VAR:entity_singular__',
        'description' => 'Actualiza un/a __VAR:entity_singular__ existente',
    ],
    'destroy' => [
        'summary' => 'Eliminar __VAR:entity_singular__',
        'description' => 'Elimina permanentemente un/a __VAR:entity_singular__',
    ],
],
```

#### Metadatos de Entidades

```php
'entity_metadata' => [
    'User' => [
        'singular' => 'Usuario',
        'plural' => 'Usuarios',
        'module' => 'Seguridad',
        'description' => 'Gestion de usuarios del sistema',
    ],
    'Role' => [
        'singular' => 'Rol',
        'plural' => 'Roles',
        'module' => 'Seguridad',
        'description' => 'Gestion de roles y permisos',
    ],
    'Product' => [
        'singular' => 'Producto',
        'plural' => 'Productos',
        'module' => 'Catalogo',
        'description' => 'Gestion del catalogo de productos',
    ],
],
```

#### Endpoints Personalizados

```php
'custom_endpoints' => [
    'api/auth/login' => [
        'post' => [
            'summary' => 'Iniciar Sesion',
            'description' => 'Autentica un usuario y retorna un token JWT',
            'tags' => ['Autenticacion'],
            'security' => [],  // Sin autenticacion requerida
            'requestBody' => [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['email', 'password'],
                            'properties' => [
                                'email' => [
                                    'type' => 'string',
                                    'format' => 'email',
                                    'example' => 'usuario@ejemplo.com',
                                ],
                                'password' => [
                                    'type' => 'string',
                                    'format' => 'password',
                                    'example' => 'MiPassword123!',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'responses' => [
                '200' => [
                    'description' => 'Login exitoso',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'token' => ['type' => 'string'],
                                    'user' => ['$ref' => '#/components/schemas/User'],
                                ],
                            ],
                        ],
                    ],
                ],
                '401' => [
                    'description' => 'Credenciales invalidas',
                ],
            ],
        ],
    ],
],
```

---

### Archivo de Tests: `config/openapi-tests.php`

```php
'test_templates' => [
    // Tests comunes para Postman
    'postman' => [
        'status_200' => 'pm.test("Status 200", function() { pm.response.to.have.status(200); });',
        'status_201' => 'pm.test("Status 201", function() { pm.response.to.have.status(201); });',
        'json_response' => 'pm.test("JSON Response", function() { pm.response.to.be.json; });',
        'has_data' => 'pm.test("Has Data", function() { pm.expect(pm.response.json()).to.have.property("data"); });',
        'save_id' => 'var json = pm.response.json(); if(json.data && json.data.id) { pm.collectionVariables.set("last_{{entity}}_id", json.data.id); }',
    ],

    // Tests para Insomnia
    'insomnia' => [
        'status_200' => 'const response = await insomnia.send(); expect(response.status).to.equal(200);',
        'status_201' => 'const response = await insomnia.send(); expect(response.status).to.equal(201);',
    ],
],

// Mapeo de acciones a tests
'action_tests' => [
    'index' => ['status_200', 'json_response', 'has_data'],
    'show' => ['status_200', 'json_response'],
    'store' => ['status_201', 'json_response', 'save_id'],
    'update' => ['status_200', 'json_response'],
    'destroy' => ['status_200_or_204'],
],
```

---

## Comandos Artisan

### Comando Principal: `openapi:generate`

#### Sintaxis Completa

```bash
php artisan openapi:generate [opciones]
```

#### Opciones Disponibles

| Opcion | Descripcion | Valores |
|--------|-------------|---------|
| `--format` | Formato de salida | `json`, `yaml` |
| `--output` | Ruta de salida personalizada | Ruta absoluta o relativa |
| `--no-cache` | Deshabilitar cache | Flag |
| `--api-type` | Filtrar por tipo de API | `api`, `site`, `mobile`, `admin` |
| `--all` | Generar todos los formatos | Flag |
| `--with-postman` | Incluir coleccion Postman | Flag |
| `--with-insomnia` | Incluir workspace Insomnia | Flag |
| `--environment` | Ambiente de entorno | `artisan`, `local`, `production` |

### Ejemplos de Uso

#### Generacion Completa

```bash
# Generar TODOS los formatos para TODOS los tipos de API
php artisan openapi:generate --all

# Salida:
# - openapi-all.json
# - openapi-all.yaml
# - postman-all.json
# - insomnia-all.json
# - postman-env-artisan.json
# - postman-env-local.json
# - postman-env-production.json
```

#### Filtrar por Tipo de API

```bash
# Solo API general
php artisan openapi:generate --all --api-type=api

# Solo API mobile
php artisan openapi:generate --all --api-type=mobile

# Multiples tipos
php artisan openapi:generate --all --api-type=api --api-type=mobile --api-type=admin
```

#### Formato Especifico

```bash
# Solo OpenAPI en JSON
php artisan openapi:generate --format=json

# Solo OpenAPI en YAML
php artisan openapi:generate --format=yaml

# OpenAPI + Postman
php artisan openapi:generate --format=json --with-postman

# OpenAPI + Insomnia
php artisan openapi:generate --format=json --with-insomnia
```

#### Ruta de Salida Personalizada

```bash
# Salida a directorio personalizado
php artisan openapi:generate --output=/var/www/docs/api-spec.json

# Salida relativa al proyecto
php artisan openapi:generate --output=./public/docs/openapi.json
```

#### Ambiente Especifico

```bash
# Generar para ambiente de produccion
php artisan openapi:generate --all --environment=production

# Generar para ambiente local
php artisan openapi:generate --all --environment=local
```

#### Sin Cache

```bash
# Forzar regeneracion sin usar cache
php artisan openapi:generate --all --no-cache
```

### Ejemplos Combinados

```bash
# Generar solo OpenAPI YAML para API mobile sin cache
php artisan openapi:generate --format=yaml --api-type=mobile --no-cache

# Generar todo para produccion excluyendo admin
php artisan openapi:generate --all --api-type=api --api-type=site --api-type=mobile --environment=production

# Generar JSON con Postman para desarrollo
php artisan openapi:generate --format=json --with-postman --environment=artisan
```

---

## Endpoints HTTP

La biblioteca expone endpoints HTTP para acceder a la documentacion dinamicamente.

### Configuracion de Rutas

```php
// config/openapi.php
'routes' => [
    'enabled' => true,
    'prefix' => 'documentation',
    'middleware' => ['web'],  // o ['api', 'auth:sanctum'] para proteger
],
```

### Endpoints Disponibles

| Metodo | Endpoint | Descripcion |
|--------|----------|-------------|
| GET | `/documentation/openapi.json` | OpenAPI en JSON |
| GET | `/documentation/openapi.yaml` | OpenAPI en YAML |
| GET | `/documentation/postman` | Coleccion Postman |
| GET | `/documentation/insomnia` | Workspace Insomnia |
| GET | `/documentation/environments/{format}` | Configuracion de entornos |
| POST | `/documentation/clear-cache` | Limpiar cache |
| GET | `/documentation/info` | Informacion de la API |

### Parametros de Query

| Parametro | Descripcion | Ejemplo |
|-----------|-------------|---------|
| `api_type` | Filtrar por tipo(s) de API | `?api_type=api,mobile` |
| `environment` | Seleccionar ambiente | `?environment=production` |

### Ejemplos con cURL

```bash
# Obtener OpenAPI JSON
curl -X GET "http://localhost:8000/documentation/openapi.json"

# Obtener OpenAPI YAML
curl -X GET "http://localhost:8000/documentation/openapi.yaml"

# Filtrar por tipo de API
curl -X GET "http://localhost:8000/documentation/openapi.json?api_type=api"

# Multiples tipos de API
curl -X GET "http://localhost:8000/documentation/openapi.json?api_type=api,mobile,admin"

# Obtener coleccion Postman para produccion
curl -X GET "http://localhost:8000/documentation/postman?environment=production"

# Obtener workspace Insomnia
curl -X GET "http://localhost:8000/documentation/insomnia?api_type=mobile"

# Limpiar cache
curl -X POST "http://localhost:8000/documentation/clear-cache"

# Obtener entornos para Postman
curl -X GET "http://localhost:8000/documentation/environments/postman"
```

### Ejemplos con Axios (JavaScript)

```javascript
// Obtener especificacion OpenAPI
const response = await axios.get('/documentation/openapi.json', {
    params: {
        api_type: 'api,mobile',
        environment: 'production'
    }
});

// Descargar coleccion Postman
const postman = await axios.get('/documentation/postman');
const blob = new Blob([JSON.stringify(postman.data)], { type: 'application/json' });
const url = window.URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = 'postman-collection.json';
a.click();
```

### Ejemplos con Guzzle (PHP)

```php
use GuzzleHttp\Client;

$client = new Client(['base_uri' => 'http://localhost:8000']);

// Obtener OpenAPI spec
$response = $client->get('/documentation/openapi.json', [
    'query' => [
        'api_type' => 'api,mobile',
        'environment' => 'production'
    ]
]);

$spec = json_decode($response->getBody(), true);

// Guardar coleccion Postman
$postman = $client->get('/documentation/postman');
file_put_contents('postman-collection.json', $postman->getBody());
```

---

## Sistema de Plantillas

### Estructura de Directorios

```
resources/openapi/templates/
├── generic/                    # Plantillas CRUD genericas
│   ├── list.json              # Listado paginado
│   ├── show.json              # Detalle de recurso
│   ├── create.json            # Creacion
│   ├── update.json            # Actualizacion
│   ├── delete.json            # Eliminacion
│   ├── validate.json          # Validacion
│   ├── bulk_update.json       # Actualizacion masiva
│   └── bulk_delete.json       # Eliminacion masiva
└── custom/                     # Plantillas personalizadas
    ├── auth.login.json
    ├── auth.register.json
    ├── auth.logout.json
    ├── auth.refresh.json
    ├── auth.user-profile.json
    └── auth.permissions.json
```

### Variables de Plantilla

Las plantillas usan sintaxis `__VAR:nombre__` para interpolacion de variables:

| Variable | Descripcion | Ejemplo |
|----------|-------------|---------|
| `__VAR:entity_plural__` | Nombre plural | "Users", "Productos" |
| `__VAR:entity_singular__` | Nombre singular | "User", "Producto" |
| `__VAR:entity_url__` | Slug URL | "users", "productos" |
| `__VAR:module__` | Nombre del modulo | "Security", "Catalogo" |
| `__VAR:fields_description__` | Descripcion de campos | "name: Nombre del usuario..." |
| `__VAR:relations_description__` | Descripcion de relaciones | "roles: Roles asignados..." |
| `__VAR:fields_list__` | Lista JSON de campos | `["id", "name", "email"]` |
| `__VAR:relations_list__` | Lista JSON de relaciones | `["roles", "permissions"]` |
| `__VAR:model_schema__` | Schema OpenAPI del modelo | `{type: "object", ...}` |
| `__VAR:request_schema__` | Schema del request body | `{type: "object", ...}` |
| `__VAR:request_example__` | Ejemplo de request | `{"name": "John", ...}` |
| `__VAR:response_example__` | Ejemplo de response | `{"data": {...}}` |
| `__VAR:validation_description__` | Reglas de validacion | "required, string, max:255" |
| `__VAR:validation_errors_example__` | Errores de validacion | `{"name": ["required"]}` |
| `__VAR:example_id__` | ID de ejemplo | "1" o "uuid-here" |

### Ejemplo de Plantilla: `list.json`

```json
{
    "summary": "Listar __VAR:entity_plural__",
    "description": "Obtiene una lista paginada de __VAR:entity_plural__.\n\n__VAR:fields_description__\n\n__VAR:relations_description__",
    "operationId": "list__VAR:entity_plural__",
    "tags": ["__VAR:module__"],
    "parameters": [
        {
            "name": "page",
            "in": "query",
            "description": "Numero de pagina",
            "schema": { "type": "integer", "default": 1 }
        },
        {
            "name": "per_page",
            "in": "query",
            "description": "Resultados por pagina",
            "schema": { "type": "integer", "default": 15 }
        },
        {
            "name": "search",
            "in": "query",
            "description": "Termino de busqueda",
            "schema": { "type": "string" }
        },
        {
            "name": "sort_by",
            "in": "query",
            "description": "Campo para ordenar",
            "schema": { "type": "string" }
        },
        {
            "name": "sort_order",
            "in": "query",
            "description": "Direccion del ordenamiento",
            "schema": { "type": "string", "enum": ["asc", "desc"] }
        },
        {
            "name": "with",
            "in": "query",
            "description": "Relaciones a incluir: __VAR:relations_list__",
            "schema": { "type": "string" }
        }
    ],
    "responses": {
        "200": {
            "description": "Lista de __VAR:entity_plural__ obtenida exitosamente",
            "content": {
                "application/json": {
                    "schema": {
                        "type": "object",
                        "properties": {
                            "data": {
                                "type": "array",
                                "items": __VAR:model_schema__
                            },
                            "meta": {
                                "type": "object",
                                "properties": {
                                    "current_page": { "type": "integer" },
                                    "last_page": { "type": "integer" },
                                    "per_page": { "type": "integer" },
                                    "total": { "type": "integer" }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
```

### Crear Plantilla Personalizada

#### Paso 1: Crear el archivo de plantilla

```bash
# Crear plantilla para endpoint de busqueda avanzada
touch resources/openapi/templates/custom/search.advanced.json
```

#### Paso 2: Definir la plantilla

```json
{
    "summary": "Busqueda avanzada de __VAR:entity_plural__",
    "description": "Realiza una busqueda avanzada con filtros multiples.\n\nCampos disponibles:\n__VAR:fields_description__",
    "operationId": "advancedSearch__VAR:entity_plural__",
    "tags": ["__VAR:module__", "Busqueda"],
    "requestBody": {
        "required": true,
        "content": {
            "application/json": {
                "schema": {
                    "type": "object",
                    "properties": {
                        "filters": {
                            "type": "array",
                            "items": {
                                "type": "object",
                                "properties": {
                                    "field": { "type": "string" },
                                    "operator": {
                                        "type": "string",
                                        "enum": ["=", "!=", ">", "<", ">=", "<=", "like", "in"]
                                    },
                                    "value": { "type": "string" }
                                }
                            }
                        },
                        "sort": {
                            "type": "object",
                            "properties": {
                                "field": { "type": "string" },
                                "direction": { "type": "string", "enum": ["asc", "desc"] }
                            }
                        }
                    }
                },
                "example": {
                    "filters": [
                        { "field": "status", "operator": "=", "value": "active" },
                        { "field": "created_at", "operator": ">=", "value": "2024-01-01" }
                    ],
                    "sort": { "field": "name", "direction": "asc" }
                }
            }
        }
    },
    "responses": {
        "200": {
            "description": "Resultados de busqueda",
            "content": {
                "application/json": {
                    "schema": {
                        "type": "object",
                        "properties": {
                            "data": {
                                "type": "array",
                                "items": __VAR:model_schema__
                            },
                            "meta": {
                                "type": "object",
                                "properties": {
                                    "total_results": { "type": "integer" },
                                    "query_time_ms": { "type": "number" }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
```

#### Paso 3: Registrar en configuracion

```php
// config/openapi-docs.php
'custom_templates' => [
    'search.advanced' => [
        'methods' => ['POST'],
        'pattern' => '*/search/advanced',
    ],
],
```

---

## Extraccion de Metadatos

### Como Funciona

La biblioteca utiliza un sistema de 4 estrategias en cascada para extraer reglas de validacion de FormRequests:

1. **Instanciacion normal**: Intenta crear la instancia directamente
2. **Inyeccion de dependencias mock**: Resuelve dependencias con mocks
3. **Reflexion sin constructor**: Usa ReflectionClass para evitar el constructor
4. **Parseo de archivo**: Analiza el codigo fuente directamente

### Reglas de Validacion Soportadas

```php
// FormRequest de ejemplo
class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Campos basicos
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::min(8)->mixedCase()->numbers()],

            // Campos opcionales
            'phone' => 'nullable|string|max:20',
            'age' => 'nullable|integer|min:18|max:120',

            // Arrays y objetos
            'tags' => 'array',
            'tags.*' => 'string|max:50',
            'address' => 'required|array',
            'address.street' => 'required|string',
            'address.city' => 'required|string',
            'address.zip' => 'required|string|regex:/^\d{5}$/',

            // Archivos
            'avatar' => 'nullable|image|mimes:jpg,png|max:2048',
            'documents' => 'array',
            'documents.*' => 'file|mimes:pdf|max:10240',

            // Relaciones
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',

            // Condicionales
            'company_name' => 'required_if:type,business|string|max:255',
            'tax_id' => 'required_with:company_name|string|max:20',

            // Reglas complejas con Rule
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users')->ignore($this->user?->id),
            ],

            // Enums
            'status' => 'required|in:active,inactive,pending',
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
        ];
    }
}
```

### Tipos OpenAPI Generados

| Regla Laravel | Tipo OpenAPI | Formato |
|--------------|--------------|---------|
| `string` | `string` | - |
| `integer` | `integer` | - |
| `numeric` | `number` | - |
| `boolean` | `boolean` | - |
| `array` | `array` | - |
| `email` | `string` | `email` |
| `url` | `string` | `uri` |
| `date` | `string` | `date` |
| `date_format:Y-m-d H:i:s` | `string` | `date-time` |
| `uuid` | `string` | `uuid` |
| `ip` | `string` | `ipv4` |
| `json` | `object` | - |
| `image`, `file` | `string` | `binary` |

### Extraccion de Modelos

```php
// Modelo de ejemplo
class User extends Model
{
    protected $fillable = [
        'name', 'email', 'password', 'phone', 'avatar'
    ];

    protected $hidden = [
        'password', 'remember_token'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'settings' => 'array',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    // Relaciones detectadas automaticamente
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }
}
```

El generador detecta automaticamente:
- Campos `fillable` y `hidden`
- Tipos de datos desde `$casts`
- Relaciones (`hasOne`, `hasMany`, `belongsTo`, `belongsToMany`, etc.)

---

## Generacion de Tests

### Tests de Postman

```javascript
// Test generado automaticamente para endpoint "store"
pm.test("Status code is 201", function() {
    pm.response.to.have.status(201);
});

pm.test("Response is JSON", function() {
    pm.response.to.be.json;
});

pm.test("Response has data object", function() {
    pm.expect(pm.response.json()).to.have.property("data");
});

pm.test("Save ID to collection variables", function() {
    var json = pm.response.json();
    if (json.data && json.data.id) {
        pm.collectionVariables.set("last_user_id", json.data.id);
    }
});
```

### Tests de Insomnia

```javascript
// Test generado para Insomnia
const response = await insomnia.send();

expect(response.status).to.equal(201);
expect(response.data).to.have.property('data');

// Guardar ID para siguientes requests
if (response.data.data && response.data.data.id) {
    insomnia.environment.set('last_user_id', response.data.data.id);
}
```

### Personalizar Tests

```php
// config/openapi-tests.php
'custom_tests' => [
    'api/users' => [
        'store' => [
            'postman' => [
                'pm.test("Email format is valid", function() {
                    var email = pm.response.json().data.email;
                    pm.expect(email).to.match(/^[^@]+@[^@]+\.[^@]+$/);
                });',
            ],
        ],
    ],
],
```

---

## Uso Programatico

### Facade

```php
use Ronu\OpenApiGenerator\Facades\OpenApiGenerator;

// Generar especificacion OpenAPI
$spec = OpenApiGenerator::generateOpenApi();

// Con opciones
$spec = OpenApiGenerator::generateOpenApi(
    useCache: false,
    apiTypes: ['api', 'mobile'],
    environment: 'production'
);

// Generar coleccion Postman
$postman = OpenApiGenerator::generatePostman(
    apiTypes: ['api'],
    environment: 'local'
);

// Generar workspace Insomnia
$insomnia = OpenApiGenerator::generateInsomnia();
```

### Servicio Directo

```php
use Ronu\OpenApiGenerator\Services\OpenApiServices;

class DocumentationController extends Controller
{
    public function __construct(
        private OpenApiServices $openApiService
    ) {}

    public function export(Request $request)
    {
        $format = $request->input('format', 'json');
        $apiTypes = $request->input('api_types', []);

        $spec = $this->openApiService->generateOpenApi(
            useCache: true,
            apiTypes: $apiTypes
        );

        if ($format === 'yaml') {
            return response($spec->toYaml())
                ->header('Content-Type', 'application/x-yaml');
        }

        return response()->json($spec->toArray());
    }
}
```

### Eventos y Hooks

```php
// AppServiceProvider.php
use Ronu\OpenApiGenerator\Events\OpenApiGenerated;
use Ronu\OpenApiGenerator\Events\PostmanGenerated;

public function boot()
{
    Event::listen(OpenApiGenerated::class, function ($event) {
        // Enviar notificacion, actualizar CDN, etc.
        Log::info('OpenAPI spec generated', [
            'api_types' => $event->apiTypes,
            'path' => $event->outputPath,
        ]);
    });

    Event::listen(PostmanGenerated::class, function ($event) {
        // Subir a Postman cloud automaticamente
        $this->uploadToPostmanCloud($event->collection);
    });
}
```

---

## Arquitectura Modular

### Soporte para Nwidart Modules

La biblioteca detecta automaticamente la estructura modular:

```
app/
├── Modules/
│   ├── User/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   └── Api/
│   │   │   │       └── UserController.php
│   │   │   └── Requests/
│   │   │       ├── StoreUserRequest.php
│   │   │       └── UpdateUserRequest.php
│   │   └── Entities/
│   │       └── User.php
│   ├── Product/
│   │   └── ...
│   └── Order/
│       └── ...
```

### Estructura de Rutas Detectada

```
api_type / module / entity / {id} / action
   |         |        |       |       |
  api     Security   users    1    permissions
```

### Configuracion para Modulos

```php
// config/openapi.php
'modules' => [
    'enabled' => true,
    'namespace' => 'Modules',
    'path' => base_path('Modules'),
],

// Mapeo de modulos a nombres legibles
'module_names' => [
    'User' => 'Gestion de Usuarios',
    'Product' => 'Catalogo de Productos',
    'Order' => 'Gestion de Pedidos',
],
```

---

## Casos de Uso Avanzados

### CI/CD Integration

```yaml
# .github/workflows/docs.yml
name: Generate API Documentation

on:
  push:
    branches: [main, develop]
    paths:
      - 'app/**'
      - 'routes/**'
      - 'Modules/**'

jobs:
  generate-docs:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install Dependencies
        run: composer install --no-dev

      - name: Generate OpenAPI Spec
        run: php artisan openapi:generate --all --no-cache

      - name: Upload to Artifacts
        uses: actions/upload-artifact@v3
        with:
          name: api-docs
          path: storage/app/public/openapi/

      - name: Deploy to SwaggerHub
        run: |
          curl -X POST "https://api.swaggerhub.com/apis/myorg/myapi" \
            -H "Authorization: Bearer ${{ secrets.SWAGGERHUB_KEY }}" \
            -H "Content-Type: application/json" \
            -d @storage/app/public/openapi/openapi-all.json
```

### Pre-commit Hook

```bash
#!/bin/bash
# .git/hooks/pre-commit

# Regenerar documentacion si cambiaron rutas o controladores
if git diff --cached --name-only | grep -E "(routes/|Controllers/|Requests/)" > /dev/null; then
    echo "Regenerando documentacion API..."
    php artisan openapi:generate --all --no-cache
    git add storage/app/public/openapi/
fi
```

### Validacion de Especificacion

```php
// tests/Feature/OpenApiSpecTest.php
use Cebe\OpenApi\Reader;
use Cebe\OpenApi\Spec\OpenApi;

class OpenApiSpecTest extends TestCase
{
    public function test_openapi_spec_is_valid()
    {
        Artisan::call('openapi:generate', ['--format' => 'json']);

        $specPath = storage_path('app/public/openapi/openapi-all.json');

        $this->assertFileExists($specPath);

        // Validar estructura OpenAPI
        $spec = Reader::readFromJsonFile($specPath);

        $this->assertInstanceOf(OpenApi::class, $spec);
        $this->assertEquals('3.0.3', $spec->openapi);
        $this->assertNotEmpty($spec->paths);
    }

    public function test_all_routes_are_documented()
    {
        $specPath = storage_path('app/public/openapi/openapi-all.json');
        $spec = json_decode(file_get_contents($specPath), true);

        $documentedPaths = array_keys($spec['paths']);

        // Obtener rutas de la aplicacion
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            if ($this->shouldBeDocumented($route)) {
                $path = '/' . $route->uri();
                $this->assertContains(
                    $this->normalizePathForOpenApi($path),
                    $documentedPaths,
                    "Route {$path} is not documented"
                );
            }
        }
    }
}
```

### Generacion Programada

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Regenerar documentacion cada noche
    $schedule->command('openapi:generate --all --no-cache')
        ->dailyAt('03:00')
        ->environments(['production'])
        ->onSuccess(function () {
            // Notificar al equipo
            Notification::send(
                User::whereRole('developer')->get(),
                new ApiDocsUpdatedNotification()
            );
        });
}
```

### Multi-tenant Documentation

```php
// Generar documentacion por tenant
foreach (Tenant::all() as $tenant) {
    tenancy()->initialize($tenant);

    Artisan::call('openapi:generate', [
        '--all' => true,
        '--output' => storage_path("tenants/{$tenant->id}/openapi/"),
    ]);

    tenancy()->end();
}
```

---

## Troubleshooting

### Problema: FormRequest no detecta reglas

**Causa**: El FormRequest tiene dependencias complejas en el constructor.

**Solucion**: La biblioteca usa 4 estrategias en cascada, pero si fallan todas:

```php
// Agregar metodo estatico para reglas
class ComplexRequest extends FormRequest
{
    public function rules(): array
    {
        return $this->getRulesArray();
    }

    // Metodo que la biblioteca puede llamar sin instanciar
    public static function documentationRules(): array
    {
        return [
            'field' => 'required|string',
            // ...
        ];
    }
}
```

### Problema: Cache no se actualiza

**Solucion**:

```bash
# Limpiar cache manualmente
php artisan openapi:generate --all --no-cache

# O via endpoint
curl -X POST http://localhost:8000/documentation/clear-cache
```

### Problema: Rutas no aparecen en documentacion

**Verificar**:

1. La ruta no esta en `exclude_routes`
2. El tipo de API esta habilitado en `api_types`
3. La ruta tiene el prefijo correcto (api, site, mobile, admin)

```php
// Verificar rutas detectadas
$routes = app(\Ronu\OpenApiGenerator\Services\OpenApiServices::class)
    ->inspectRoutes();

dd($routes);
```

### Problema: Tipos de datos incorrectos

**Solucion**: Especificar tipos explicitamente en FormRequest:

```php
// Usar PHPDoc para ayudar a la deteccion
class StoreRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /** @var string */
            'name' => 'required|string',
            /** @var int */
            'age' => 'required|integer',
        ];
    }
}
```

### Problema: Relaciones no detectadas

**Solucion**: Asegurar que las relaciones tengan tipo de retorno:

```php
// Incorrecto - no detecta
public function roles()
{
    return $this->belongsToMany(Role::class);
}

// Correcto - detecta automaticamente
public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class);
}
```

### Problema: Error de memoria

**Solucion**: Aumentar limite de memoria o usar filtros:

```bash
# Aumentar memoria
php -d memory_limit=512M artisan openapi:generate --all

# O generar por tipo
php artisan openapi:generate --api-type=api
php artisan openapi:generate --api-type=mobile
```

---

## Recursos Adicionales

- [OpenAPI Specification](https://spec.openapis.org/oas/v3.0.3)
- [Postman Collection Format](https://learning.postman.com/collection-format/getting-started/overview/)
- [Insomnia Documentation](https://docs.insomnia.rest/)
- [Laravel Validation Rules](https://laravel.com/docs/validation#available-validation-rules)

---

**Licencia**: MIT
**Autor**: Ronu
**Repositorio**: https://github.com/charlietyn/openapi-generator
