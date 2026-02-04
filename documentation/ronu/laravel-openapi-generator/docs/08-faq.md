# FAQ

## ¿Qué formatos exporta?
OpenAPI 3.0.3 (JSON/YAML), Postman v2.1 e Insomnia v4.

Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Flags `--with-postman`, `--with-insomnia`.
- File: src/Services/InsomniaWorkspaceGenerator.php
  - Notes: Workspace v4.
- File: src/Services/PostmanCollectionGenerator.php
  - Notes: Collection v2.1.

## ¿Dónde se guardan los archivos generados?
En `config('openapi.output_path')`, por defecto `storage/app/public/openapi`.

Evidence:
- File: config/openapi.php
  - Notes: `output_path`.
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Usa `output_path`.

## ¿Se soporta el alias “movile”? 
Sí, existe un alias legacy `movile` → `mobile` tanto en CLI como HTTP.

Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: `normalizeApiTypes()` (alias).
- File: src/Controllers/OpenApiController.php
  - Notes: `parseApiTypes()` (alias).
- File: src/Services/OpenApiServices.php
  - Notes: `normalizeApiTypes()`.

## ¿Los templates deben ser JSON válido?
Sí. El renderizador valida el JSON antes y después de procesar.

Evidence:
- File: src/Services/Documentation/ValidJSONTemplateProcessor.php
  - Notes: `validateJSON()` en `process()`.

## Evidence
- File: src/Services/Documentation/ValidJSONTemplateProcessor.php
  - Notes: Requisito de JSON válido.
