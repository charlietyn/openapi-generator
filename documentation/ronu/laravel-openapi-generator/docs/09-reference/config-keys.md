# Config keys reference

## config/openapi.php

| Key | Purpose | Evidence |
| --- | --- | --- |
| `info` | Metadata del API (title, description, version, contact, license). | config/openapi.php (`info`) |
| `servers` | Lista de servidores (URLs) para el spec. | config/openapi.php (`servers`) |
| `security` | Esquemas de seguridad (Bearer, ApiKey). | config/openapi.php (`security`) |
| `environments` | Entornos para Postman/Insomnia. | config/openapi.php (`environments`) |
| `api_types` | Definición de canales y prefijos. | config/openapi.php (`api_types`) |
| `modules_path` | Ruta base de módulos. | config/openapi.php (`modules_path`) |
| `cache` | Cache del spec (`enabled`, `ttl`, `key_prefix`). | config/openapi.php (`cache`) |
| `output_path` | Directorio de salida de archivos. | config/openapi.php (`output_path`) |
| `paths.models` | Namespaces de modelos a escanear. | config/openapi.php (`paths.models`) |
| `paths.requests` | Namespaces de FormRequests a escanear. | config/openapi.php (`paths.requests`) |
| `exclude_routes` | Patrones de rutas excluidas. | config/openapi.php (`exclude_routes`) |
| `middleware_security_map` | Map de middleware → security scheme. | config/openapi.php (`middleware_security_map`) |
| `response_examples` | Ejemplos de respuestas por status code. | config/openapi.php (`response_examples`) |
| `routes` | Configuración de rutas HTTP. | config/openapi.php (`routes`) |

Evidence:
- File: config/openapi.php
  - Notes: Claves listadas.

## config/openapi-docs.php

| Key | Purpose | Evidence |
| --- | --- | --- |
| `crud_templates` | Textos para endpoints CRUD. | config/openapi-docs.php (`crud_templates`) |
| `entities` | Metadata por entidad (model, description). | config/openapi-docs.php (`entities`) |
| `custom_endpoints` | Descripción de endpoints custom. | config/openapi-docs.php (`custom_endpoints`) |
| `auto_detect` | Auto-detección de metadata. | config/openapi-docs.php (`auto_detect`) |
| `field_descriptions` | Descripciones comunes de campos. | config/openapi-docs.php (`field_descriptions`) |
| `priority` | Orden de resolución de docs. | config/openapi-docs.php (`priority`) |

Evidence:
- File: config/openapi-docs.php
  - Notes: Claves listadas.

## config/openapi-tests.php

| Key | Purpose | Evidence |
| --- | --- | --- |
| `templates` | Checks por acción. | config/openapi-tests.php (`templates`) |
| `snippets` | Snippets por check y formato. | config/openapi-tests.php (`snippets`) |
| `custom_tests` | Overrides por endpoint. | config/openapi-tests.php (`custom_tests`) |
| `settings` | Config global de tests (verbose, timeout, etc.). | config/openapi-tests.php (`settings`) |
| `performance` | Snippets de performance. | config/openapi-tests.php (`performance`) |

Evidence:
- File: config/openapi-tests.php
  - Notes: Claves listadas.

## config/openapi-templates.php

| Key | Purpose | Evidence |
| --- | --- | --- |
| `enabled` | Activa/desactiva templates JSON. | config/openapi-templates.php (`enabled`) |
| `paths` | Rutas de templates (generic/custom). | config/openapi-templates.php (`paths`) |
| `generic_templates` | Alias de acciones a templates. | config/openapi-templates.php (`generic_templates`) |
| `query_builder` | Docs de operadores/paginación. | config/openapi-templates.php (`query_builder`) |
| `auto_detect` | Auto-detección de relations/fields/casts. | config/openapi-templates.php (`auto_detect`) |
| `entity_descriptions` | Descripciones manuales. | config/openapi-templates.php (`entity_descriptions`) |
| `rendering` | Debug/cache/validación de templates. | config/openapi-templates.php (`rendering`) |
| `filters` | Filtros personalizados. | config/openapi-templates.php (`filters`) |
| `validation` | Reglas de validación de templates. | config/openapi-templates.php (`validation`) |
| `examples` | Config de ejemplos. | config/openapi-templates.php (`examples`) |
| `performance` | Config de rendimiento. | config/openapi-templates.php (`performance`) |

Evidence:
- File: config/openapi-templates.php
  - Notes: Claves listadas.

## Evidence
- File: config/openapi.php
  - Notes: Config principal.
