# 03-findings.md

## High

### H-1: `env()` usado en runtime (rompe `config:cache`)
- **Evidence**
  - File: `src/Helpers/PlaceholderHelper.php`
  - Symbol: `PlaceholderHelper::getProjectName`
  - Snippet:
    ```php
    public static function getProjectName(): string
    {
        $appName = env('APP_NAME', 'proyecto');
    ```
  - Reason: `env()` fuera de `config/` queda congelado con `config:cache`.
- **Impact**: Valores en `.env` podrían no reflejarse o romperse en producción.
- **Proposed fix**: Pasar a `config('app.name')` o a `config('openapi.info.title')` y documentar la clave.

### H-2: `env()` en runtime para `APP_URL`
- **Evidence**
  - File: `src/Services/EnvironmentGenerator.php`
  - Symbol: `EnvironmentGenerator::buildBaseEnvironmentData`
  - Snippet:
    ```php
    protected function buildBaseEnvironmentData(): array
    {
        return [
            'base_url' => env('APP_URL', 'http://localhost:8000'),
    ```
  - Reason: `env()` en runtime fuera de `config/`.
- **Impact**: Config cache puede ignorar cambios de `APP_URL`.
- **Proposed fix**: Usar `config('app.url')` o `config('openapi.environments.base.variables.base_url')`.

### H-3: `env()` en runtime para `API_VERSION`
- **Evidence**
  - File: `src/Services/OpenApiServices.php`
  - Symbol: `OpenApiServices::buildDynamicInfo`
  - Snippet:
    ```php
    'version' => env('API_VERSION', '1.0.0'),
    ```
  - Reason: `env()` fuera de `config/`.
- **Impact**: `API_VERSION` puede no actualizarse con config cache.
- **Proposed fix**: Usar `config('openapi.info.version')`.

## Medium

### M-1: Config key roto (`openapi_templates.*`)
- **Evidence**
  - File: `src/Services/OpenApiServices.php`
  - Symbol: `OpenApiServices::applyDocumentationTemplate`
  - Snippet:
    ```php
    $templates = config('openapi_templates.resource_descriptions', []);
    ```
  - Reason: No existe `config/openapi_templates.php` ni `openapi_templates`.
- **Impact**: Configuración de templates por recurso nunca se aplica.
- **Proposed fix**: Renombrar a `openapi-templates.*` y añadir `resource_descriptions` en config, o mover a `openapi-docs` (requiere confirmación).

### M-2: `config/openapi-docs.php` publishable pero no leído
- **Evidence**
  - File: `src/Providers/OpenApiGeneratorServiceProvider.php`
  - Symbol: `OpenApiGeneratorServiceProvider::boot`
  - Snippet:
    ```php
    $this->publishes([
        __DIR__ . '/../../config/openapi-docs.php' => config_path('openapi-docs.php'),
    ], 'openapi-docs');
    ```
  - Reason: No hay llamadas `config('openapi-docs.*')` en el runtime.
- **Impact**: Configuración documentada puede ser ignorada.
- **Proposed fix**: Confirmar intención; si se usa, integrar en runtime. Si no, deprecate y remover.

### M-3: Claves `openapi-templates` declaradas pero no usadas
- **Evidence**
  - File: `config/openapi-templates.php`
  - Symbol: `return` array
  - Snippet:
    ```php
    'enabled' => env('OPENAPI_TEMPLATES_ENABLED', true),
    'paths' => [
        'generic' => resource_path('openapi/templates/generic'),
    ```
  - Reason: Solo se consumen `rendering.debug`, `rendering.cache_enabled`, `entity_descriptions`.
- **Impact**: Configuración engañosa y variables ENV sin efecto real.
- **Proposed fix**: Implementar lectura o marcar como deprecated.

### M-4: `openapi-tests.verbose_logging` no usado
- **Evidence**
  - File: `config/openapi-tests.php`
  - Symbol: `return` array
  - Snippet:
    ```php
    'verbose_logging' => env('OPENAPI_TESTS_VERBOSE', false),
    ```
  - Reason: No hay `config('openapi-tests.verbose_logging')` en el runtime.
- **Impact**: Variable ENV sin efecto.
- **Proposed fix**: Usar o deprecate.

## Low

### L-1: Rutas OpenAPI habilitadas por defecto sin middleware
- **Evidence**
  - File: `config/openapi.php`
  - Symbol: `routes`
  - Snippet:
    ```php
    'routes' => [
        'enabled' => env('OPENAPI_ROUTES_ENABLED', true),
        'middleware' => explode(',', env('OPENAPI_ROUTES_MIDDLEWARE', '')),
    ],
    ```
  - Reason: Default `true` y middleware vacío.
- **Impact**: Exposición pública de docs/exports en producción si no se asegura.
- **Proposed fix**: Recomendar middleware auth o deshabilitar por defecto en docs.
