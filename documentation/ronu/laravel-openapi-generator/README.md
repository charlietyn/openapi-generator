# Ronu Laravel OpenAPI Generator

## ¿Qué es y qué resuelve?

**ronu/laravel-openapi-generator** es un paquete Laravel que genera documentación OpenAPI 3.0.3, colecciones Postman v2.1 y workspaces Insomnia v4 a partir de tus rutas y metadatos existentes (FormRequests, modelos y templates JSON). Está diseñado para reducir el trabajo manual y estandarizar la documentación en múltiples formatos sin anotaciones en el código de controllers.  
Evidence:
- File: composer.json
  - Notes: Nombre del paquete, dependencias Laravel/PHP y auto-discovery.
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::generate()
  - Notes: Orquesta la generación de OpenAPI/Postman/Insomnia.
- File: src/Services/PostmanCollectionGenerator.php
  - Symbol: PostmanCollectionGenerator::generate()
  - Notes: Exporta Postman v2.1.
- File: src/Services/InsomniaWorkspaceGenerator.php
  - Symbol: InsomniaWorkspaceGenerator::generate()
  - Notes: Exporta Insomnia v4.
- File: src/Services/Documentation/MetadataExtractor.php
  - Symbol: MetadataExtractor::extractForEntity()
  - Notes: Extrae metadatos desde FormRequests y modelos.

## Conceptos clave

- **API Types**: Segmentación por canales (admin/site/mobile) con prefijos y filtros.  
  Evidence:
  - File: config/openapi.php
    - Notes: Sección `api_types`.
  - File: src/Services/OpenApiServices.php
    - Symbol: OpenApiServices::setApiTypeFilter()
    - Notes: Normaliza y valida filtros.
- **Templates JSON**: Sistema de plantillas JSON válidas para describir operaciones CRUD y endpoints custom.  
  Evidence:
  - File: config/openapi-templates.php
    - Notes: Rutas y mapping de templates.
  - File: src/Services/Documentation/TemplateDocumentationResolver.php
    - Symbol: TemplateDocumentationResolver::resolveForOperation()
    - Notes: Prioriza templates custom/generic.
  - File: src/Services/Documentation/ValidJSONTemplateProcessor.php
    - Symbol: ValidJSONTemplateProcessor::process()
    - Notes: Exige JSON válido antes y después del render.
- **Entornos**: Generación de environments para Postman/Insomnia con herencia.  
  Evidence:
  - File: config/openapi.php
    - Notes: Sección `environments`.
  - File: src/Services/EnvironmentGenerator.php
    - Symbol: EnvironmentGenerator::generatePostman()
    - Notes: Crea variables y jerarquías.

## Quickstart

1) Instala el paquete:
```bash
composer require ronu/laravel-openapi-generator
```

2) Publica la configuración:
```bash
php artisan vendor:publish --tag=openapi-config
```

3) (Opcional) Publica templates para personalizar:
```bash
php artisan vendor:publish --tag=openapi-templates
```

4) Genera documentación:
```bash
php artisan openapi:generate --all
```

Evidence:
- File: composer.json
  - Notes: Auto-discovery y dependencias.
- File: src/Providers/OpenApiGeneratorServiceProvider.php
  - Notes: Publish tags `openapi-config` y `openapi-templates`, registro de comando.
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Comando `openapi:generate` y flags.

## Índice

- [Installation](docs/01-installation.md)
- [Configuration](docs/02-configuration.md)
- [Architecture](docs/03-architecture.md)
- [Features](docs/04-features/)
  - [Generación vía Artisan](docs/04-features/openapi-generation-cli.md)
  - [Endpoints HTTP](docs/04-features/http-endpoints.md)
  - [Export Postman + Insomnia](docs/04-features/postman-insomnia-export.md)
  - [Template-based docs](docs/04-features/template-documentation.md)
  - [Metadata extraction](docs/04-features/metadata-extraction.md)
  - [Environment management](docs/04-features/environment-management.md)
  - [Route filtering](docs/04-features/route-filtering.md)
  - [Caching](docs/04-features/caching.md)
- [Security](docs/05-security.md)
- [Testing](docs/06-testing.md)
- [Troubleshooting](docs/07-troubleshooting.md)
- [FAQ](docs/08-faq.md)
- [Reference](docs/09-reference/)
- [Migration Guide](docs/10-migration-guide.md)
- [Contributing](docs/11-contributing.md)
- [License](docs/12-license.md)

## Requisitos

- **PHP**: ^8.1
- **Laravel (illuminate/*)**: ^10.0 | ^11.0 | ^12.0

Evidence:
- File: composer.json
  - Notes: Secciones `require`.

## Common integration paths

- **CLI-only**: Generación bajo demanda con `php artisan openapi:generate`.  
  Evidence:
  - File: src/Commands/GenerateOpenApiSpec.php
    - Notes: Comando con opciones para formatos y filtros.
- **HTTP endpoints**: Rutas para descargar OpenAPI/Postman/Insomnia.  
  Evidence:
  - File: src/routes/web.php
    - Notes: Rutas `documentation/openapi.{format}`, `documentation/postman`, `documentation/insomnia`.
- **Uso programático**: Inyectar `OpenApiGenerator` o usar el facade `OpenApiGenerator`.  
  Evidence:
  - File: src/OpenApiGenerator.php
    - Notes: Métodos `generateOpenApi`, `generatePostman`, `generateInsomnia`.
  - File: src/Facades/OpenApiGenerator.php
    - Notes: Facade para acceso rápido.

## Notas de discrepancias con docs existentes

Al revisar `docs/` del repositorio, se observaron diferencias con el código actual:

- **Documentación menciona comandos `openapi:validate` y `openapi:clear-cache`**, pero en el código actual solo existe `openapi:generate`.  
  Evidence:
  - File: docs/INSTALLATION.md
    - Notes: Lista comandos adicionales.
  - File: src/Commands/GenerateOpenApiSpec.php
    - Notes: Único comando registrado.
- **Sección de rutas HTTP en docs existentes** asume rutas disponibles; en el código no hay `loadRoutesFrom()` en el Service Provider. Se deben cargar manualmente si tu app no lo hace.  
  Evidence:
  - File: src/Providers/OpenApiGeneratorServiceProvider.php
    - Notes: No registra rutas.
  - File: src/routes/web.php
    - Notes: Definiciones de rutas existentes.

## Evidence
- File: README.md
  - Notes: Documentación previa en inglés con ejemplos generales.
- File: docs/INSTALLATION.md
  - Notes: Reglas de instalación previas para contraste.
