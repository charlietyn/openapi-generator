# Export Postman + Insomnia

## Overview
Genera colecciones Postman v2.1 e Insomnia Workspace v4 a partir del spec OpenAPI generado. Incluye estructura jerárquica por API Type → Módulo → Entidad y tests automáticos.  
Evidence:
- File: src/Services/PostmanCollectionGenerator.php
  - Notes: Generador Postman con estructura jerárquica.
- File: src/Services/InsomniaWorkspaceGenerator.php
  - Notes: Generador Insomnia v4.
- File: src/Services/TestTemplateResolver.php
  - Notes: Templates de tests para ambos formatos.

## When to use / When NOT to use

**Úsalo cuando**:
- Tu equipo trabaja en Postman o Insomnia y necesita environments preconfigurados.

**No lo uses cuando**:
- Solo requieres OpenAPI puro para integraciones automáticas.

Evidence:
- File: src/Services/PostmanCollectionGenerator.php
  - Notes: Export Postman.
- File: src/Services/InsomniaWorkspaceGenerator.php
  - Notes: Export Insomnia.

## How it works (internals resumidos)

1) `OpenApiServices` genera el spec base.
2) Se llama al generador Postman/Insomnia según formato.
3) Se inyectan scripts de test según `openapi-tests`.

Evidence:
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::convertToFormat()
  - Notes: Delegación a generadores por formato.
- File: src/Services/TestTemplateResolver.php
  - Symbol: TestTemplateResolver::generateTestScript()
  - Notes: Construcción de tests.

## Configuration (keys/env involucradas)

- `openapi-tests.templates`
- `openapi-tests.snippets`
- `openapi.environments`

Evidence:
- File: config/openapi-tests.php
  - Notes: Templates/snippets.
- File: config/openapi.php
  - Notes: Environments usados en exports.

## Usage examples

```bash
# CLI: generar OpenAPI + Postman + Insomnia
php artisan openapi:generate --all

# CLI: solo Postman
php artisan openapi:generate --with-postman

# CLI: solo Insomnia
php artisan openapi:generate --with-insomnia
```

```http
# HTTP: Postman
GET /documentation/postman

# HTTP: Insomnia
GET /documentation/insomnia
```

Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Flags `--with-postman`, `--with-insomnia`, `--all`.
- File: src/routes/web.php
  - Notes: Rutas HTTP para Postman/Insomnia.

## Edge cases / pitfalls

- Si el spec no contiene rutas, el generador devuelve colecciones vacías.
- Los tests se basan en `openapi-tests`; si faltan snippets, los checks no se agregan.

Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Manejo de rutas vacías.
- File: src/Services/TestTemplateResolver.php
  - Notes: `getCheckCode()` retorna vacío si no hay snippet.

## Related docs

- [Testing](../06-testing.md)
- [Reference: config keys](../09-reference/config-keys.md)
- [Reference: env vars](../09-reference/env-vars.md)

## Evidence
- File: src/Services/PostmanCollectionGenerator.php
  - Notes: Export Postman.
