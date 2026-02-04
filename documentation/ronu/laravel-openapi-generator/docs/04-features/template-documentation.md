# Template-based documentation

## Overview
El paquete usa templates JSON para generar descripciones, ejemplos y respuestas de endpoints. Prioriza templates custom y luego templates genéricos con fallback.  
Evidence:
- File: src/Services/Documentation/TemplateDocumentationResolver.php
  - Notes: Resolución custom → generic → fallback.
- File: resources/templates
  - Notes: Templates base incluidos.

## When to use / When NOT to use

**Úsalo cuando**:
- Necesitas estandarizar respuestas y descripciones por acción.
- Quieres customizar documentación sin tocar el código.

**No lo uses cuando**:
- Prefieres documentación manual o no deseas templates JSON.

Evidence:
- File: config/openapi-templates.php
  - Notes: Permite activar/desactivar templates.

## How it works (internals resumidos)

1) `TemplateDocumentationResolver` busca template custom: `resources/openapi/templates/custom/{entity}.{action}.json`.
2) Si no existe, busca template genérico en `generic/{action}.json`.
3) Renderiza con `ValidJSONTemplateProcessor`, que exige JSON válido antes y después.

Evidence:
- File: src/Services/Documentation/TemplateDocumentationResolver.php
  - Symbol: TemplateDocumentationResolver::findCustomTemplate()
  - Notes: Resolución de paths custom.
- File: src/Services/Documentation/TemplateDocumentationResolver.php
  - Symbol: TemplateDocumentationResolver::findGenericTemplate()
  - Notes: Resolución de templates genéricos y aliases.
- File: src/Services/Documentation/ValidJSONTemplateProcessor.php
  - Symbol: ValidJSONTemplateProcessor::process()
  - Notes: Validación de JSON.

## Configuration (keys/env involucradas)

- `openapi-templates.enabled`
- `openapi-templates.paths.generic`
- `openapi-templates.paths.custom`
- `openapi-templates.generic_templates`
- `openapi-templates.rendering.*`

Evidence:
- File: config/openapi-templates.php
  - Notes: Configuración del sistema de templates.

## Usage examples

```bash
# Publicar templates para personalizar
php artisan vendor:publish --tag=openapi-templates
```

```json
// resources/openapi/templates/custom/users.store.json
{
  "summary": "Create __VAR:entity_singular__",
  "description": "Creates a new __VAR:entity_singular__.",
  "responses": {
    "201": "Created"
  }
}
```

Evidence:
- File: src/Providers/OpenApiGeneratorServiceProvider.php
  - Notes: Publish de templates.
- File: src/Services/Documentation/ValidJSONTemplateProcessor.php
  - Notes: Sintaxis de placeholders.

## Edge cases / pitfalls

- Los templates **deben** ser JSON válido; de lo contrario el render devuelve `{}`.
- Si no existe template custom ni generic, se usa fallback genérico.

Evidence:
- File: src/Services/Documentation/ValidJSONTemplateProcessor.php
  - Notes: Validación de JSON y fallback.
- File: src/Services/Documentation/TemplateDocumentationResolver.php
  - Notes: Uso de fallback.

## Related docs

- [Configuration](../02-configuration.md)
- [Reference: config keys](../09-reference/config-keys.md)

## Evidence
- File: resources/templates/generic
  - Notes: Templates genéricos incluidos.
