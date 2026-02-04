# Metadata extraction

## Overview
El generador extrae metadatos de **FormRequests** y **Modelos** para construir schemas, ejemplos y validaciones automáticamente.  
Evidence:
- File: src/Services/Documentation/MetadataExtractor.php
  - Notes: Métodos de extracción de reglas, schemas y ejemplos.

## When to use / When NOT to use

**Úsalo cuando**:
- Tus endpoints usan FormRequests y modelos Eloquent.

**No lo uses cuando**:
- No hay FormRequests ni modelos asociados (la salida será más básica).

Evidence:
- File: src/Services/Documentation/MetadataExtractor.php
  - Notes: Maneja ausencia de clases.

## How it works (internals resumidos)

1) Detecta clase de modelo por nombre de entidad.
2) Busca FormRequest asociado (por convención o por método).
3) Genera reglas, schemas y ejemplos a partir de reglas.

Evidence:
- File: src/Services/Documentation/MetadataExtractor.php
  - Symbol: MetadataExtractor::findModelClass()
  - Notes: Detección de modelos.
- File: src/Services/Documentation/MetadataExtractor.php
  - Symbol: MetadataExtractor::findFormRequest()
  - Notes: Detección de FormRequest.
- File: src/Services/Documentation/MetadataExtractor.php
  - Symbol: MetadataExtractor::generateRequestSchemaFromFormRequest()
  - Notes: Construcción de schema desde reglas.

## Configuration (keys/env involucradas)

- `openapi.paths.models`
- `openapi.paths.requests`
- `openapi-templates.auto_detect.*`
- `openapi-docs.auto_detect`

Evidence:
- File: config/openapi.php
  - Notes: Sección `paths` para modelos/requests.
- File: config/openapi-templates.php
  - Notes: `auto_detect` para relaciones, validations, etc.
- File: config/openapi-docs.php
  - Notes: `auto_detect` general.

## Usage examples

```php
// config/openapi.php
'paths' => [
    'models' => ['App\\Models', 'Modules\\{module}\\Entities'],
    'requests' => ['App\\Http\\Requests', 'Modules\\{module}\\Http\\Requests'],
],
```

Evidence:
- File: config/openapi.php
  - Notes: Paths por defecto.

## Edge cases / pitfalls

- Si la clase no existe, la extracción devuelve metadata parcial.
- La detección depende de convenciones de nombres.

Evidence:
- File: src/Services/Documentation/MetadataExtractor.php
  - Notes: Logs y retornos en caso de no encontrar clases.

## Related docs

- [Configuration](../02-configuration.md)
- [Reference: config keys](../09-reference/config-keys.md)

## Evidence
- File: src/Services/Documentation/MetadataExtractor.php
  - Notes: Clase de extracción principal.
