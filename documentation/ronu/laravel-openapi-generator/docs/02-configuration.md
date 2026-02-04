# Configuration

## Archivos de configuración

El paquete publica cuatro archivos principales:

- `config/openapi.php` (core: info, servers, api_types, cache, rutas)
- `config/openapi-docs.php` (metadata de entidades y endpoints custom)
- `config/openapi-tests.php` (plantillas de tests para Postman/Insomnia)
- `config/openapi-templates.php` (sistema de templates JSON)

Evidence:
- File: src/Providers/OpenApiGeneratorServiceProvider.php
  - Notes: Publica los cuatro archivos.
- File: config/openapi.php
  - Notes: Config principal.
- File: config/openapi-docs.php
  - Notes: CRUD templates y custom endpoints.
- File: config/openapi-tests.php
  - Notes: Templates de tests y snippets.
- File: config/openapi-templates.php
  - Notes: Config del sistema de templates.

## Configuración base (openapi.php)

### Metadata del API
Define `info` para título, descripción, versión y contacto. Incluye placeholders `${{projectName}}` que se reemplazan según `APP_NAME`.

Evidence:
- File: config/openapi.php
  - Notes: Sección `info`.
- File: src/Helpers/PlaceholderHelper.php
  - Notes: Reemplazo de `${{projectName}}`.

### Servers
Se definen servidores para distintos entornos; el generador reemplaza placeholders al producir la spec.

Evidence:
- File: config/openapi.php
  - Notes: Sección `servers`.
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::buildDynamicServers()
  - Notes: Reemplazo dinámico de placeholders.

### API Types (filtros)
Puedes activar/desactivar canales y definir prefijos y nombres de carpeta.

Evidence:
- File: config/openapi.php
  - Notes: Sección `api_types`.
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::setApiTypeFilter()
  - Notes: Normaliza y valida filtros.

### Rutas HTTP
Habilita/inhabilita endpoints HTTP y define prefix/middleware.

Evidence:
- File: config/openapi.php
  - Notes: Sección `routes`.
- File: src/routes/web.php
  - Notes: Usa `openapi.routes.prefix` y `openapi.routes.middleware`.

### Cache
Activa/desactiva cache y TTL.

Evidence:
- File: config/openapi.php
  - Notes: Sección `cache`.
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::generate()
  - Notes: Cache de resultados con `Cache::get/put`.

## Configuración de documentación (openapi-docs.php)

- **crud_templates**: textos por acción CRUD.
- **entities**: metadata por entidad.
- **custom_endpoints**: documentación de endpoints no CRUD.
- **priority**: orden de resolución.

Evidence:
- File: config/openapi-docs.php
  - Notes: Secciones `crud_templates`, `entities`, `custom_endpoints`, `priority`.
- File: src/Services/Documentation/TemplateDocumentationResolver.php
  - Symbol: TemplateDocumentationResolver::resolveForOperation()
  - Notes: Usa templates/metadata para describir endpoints.

## Configuración de tests (openapi-tests.php)

Define qué checks se ejecutan para cada acción y los snippets por formato (Postman/Insomnia).

Evidence:
- File: config/openapi-tests.php
  - Notes: `templates` y `snippets`.
- File: src/Services/TestTemplateResolver.php
  - Symbol: TestTemplateResolver::generateTestScript()
  - Notes: Resuelve checks/snippets según acción y formato.

## Configuración de templates (openapi-templates.php)

Permite:
- Activar/desactivar el sistema de templates.
- Definir rutas de templates.
- Configurar auto-detección y rendering.

Evidence:
- File: config/openapi-templates.php
  - Notes: Config del sistema de templates.
- File: src/Services/Documentation/ValidJSONTemplateProcessor.php
  - Symbol: ValidJSONTemplateProcessor::process()
  - Notes: Renderiza templates JSON válidos.

## Evidence
- File: config/openapi.php
  - Notes: Config principal.
