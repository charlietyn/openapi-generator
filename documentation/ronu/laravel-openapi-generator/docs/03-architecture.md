# Architecture

## Vista general

El paquete se compone de un **orquestador** (`OpenApiServices`) y generadores por formato (OpenAPI/Postman/Insomnia), además de un sistema de templates y extracción de metadata desde modelos/FormRequests.

```
Route Inspection
    ↓
OpenApiServices (orchestrator)
    ├─ DocumentationResolver
    │   └─ TemplateDocumentationResolver
    │       └─ ValidJSONTemplateProcessor
    ├─ MetadataExtractor
    ├─ PostmanCollectionGenerator
    ├─ InsomniaWorkspaceGenerator
    └─ EnvironmentGenerator
```

Evidence:
- File: src/Services/OpenApiServices.php
  - Notes: Orquestador principal.
- File: src/Services/Documentation/DocumentationResolver.php
  - Notes: Resuelve documentación por operación.
- File: src/Services/Documentation/TemplateDocumentationResolver.php
  - Notes: Selección y render de templates.
- File: src/Services/Documentation/ValidJSONTemplateProcessor.php
  - Notes: Render JSON válido.
- File: src/Services/PostmanCollectionGenerator.php
  - Notes: Export Postman.
- File: src/Services/InsomniaWorkspaceGenerator.php
  - Notes: Export Insomnia.
- File: src/Services/EnvironmentGenerator.php
  - Notes: Variables y entornos.

## Flujo de generación (CLI o HTTP)

1) Se invoca `OpenApiServices::generate()` desde el comando o controlador.
2) Se inspeccionan rutas (`Route::getRoutes()`), con filtros por API type y exclusiones.
3) Se extrae metadata de modelos/FormRequests.
4) Se resuelven templates JSON (custom/generic/fallback).
5) Se genera OpenAPI o se convierte a Postman/Insomnia.
6) Se guarda el artefacto en `config('openapi.output_path')`.

Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: CLI llama `OpenApiServices::generate()`.
- File: src/Controllers/OpenApiController.php
  - Notes: HTTP llama `OpenApiServices::generate()`.
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::inspectRoutes()
  - Notes: Inspección de rutas y filtros.
- File: src/Services/Documentation/MetadataExtractor.php
  - Symbol: MetadataExtractor::extractForEntity()
  - Notes: Extracción de metadata.
- File: src/Services/Documentation/TemplateDocumentationResolver.php
  - Symbol: TemplateDocumentationResolver::resolveForOperation()
  - Notes: Resolución de templates.

## Componentes clave

### OpenApiServices (orquestador)
- Construye el spec base.
- Aplica environment a los `servers`.
- Convierte a Postman/Insomnia si aplica.

Evidence:
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::initializeSpec()
  - Notes: Inicializa OpenAPI base.
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::applyEnvironment()
  - Notes: Ajusta servers por environment.
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::convertToFormat()
  - Notes: Convierte a Postman/Insomnia.

### DocumentationResolver + TemplateDocumentationResolver
- Decide módulo por namespace del controller.
- Selecciona template custom o genérico.

Evidence:
- File: src/Services/Documentation/DocumentationResolver.php
  - Symbol: DocumentationResolver::resolveForOperation()
  - Notes: Orquesta resolución.
- File: src/Services/Documentation/TemplateDocumentationResolver.php
  - Symbol: TemplateDocumentationResolver::findCustomTemplate()
  - Notes: Prioriza templates custom.

### MetadataExtractor
- Busca modelos y FormRequests.
- Genera schemas, ejemplos y reglas.

Evidence:
- File: src/Services/Documentation/MetadataExtractor.php
  - Symbol: MetadataExtractor::extractForEntity()
  - Notes: Crea metadata completa.

## Evidence
- File: src/Services/OpenApiServices.php
  - Notes: Implementación central.
