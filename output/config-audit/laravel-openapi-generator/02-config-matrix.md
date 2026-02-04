# 02-config-matrix.md

## Matriz de claves de configuración

| Config key | Declared in | Used in | Suggested action | Evidence |
| --- | --- | --- | --- | --- |
| `openapi.info` | `config/openapi.php` | `src/Services/OpenApiServices.php`, `src/Controllers/OpenApiController.php` | KEEP | Usado para `info` y títulos. |
| `openapi.servers` | `config/openapi.php` | `src/Services/OpenApiServices.php` | KEEP | Servidores dinámicos con placeholders. |
| `openapi.security` | `config/openapi.php` | **No runtime usage** | INVESTIGATE / REMOVE | No hay `config('openapi.security')` en código. |
| `openapi.environments.*` | `config/openapi.php` | `src/Services/EnvironmentGenerator.php`, `src/Services/OpenApiServices.php`, `src/Controllers/OpenApiController.php` | KEEP | Usado para Postman/Insomnia. |
| `openapi.api_types` | `config/openapi.php` | `src/Services/OpenApiServices.php`, `src/Commands/GenerateOpenApiSpec.php`, `src/Services/PostmanCollectionGenerator.php`, `src/Services/InsomniaWorkspaceGenerator.php` | KEEP | Filtrado por tipo. |
| `openapi.modules_path` | `config/openapi.php` | `src/Services/OpenApiServices.php` | KEEP | Nwidart modules. |
| `openapi.cache.*` | `config/openapi.php` | `src/Services/OpenApiServices.php` | KEEP | Cache spec. |
| `openapi.output_path` | `config/openapi.php` | `src/Commands/GenerateOpenApiSpec.php`, `src/Controllers/OpenApiController.php` | KEEP | Output exports. |
| `openapi.paths.*` | `config/openapi.php` | **No runtime usage** | INVESTIGATE / REMOVE | No hay `config('openapi.paths')`. |
| `openapi.exclude_routes` | `config/openapi.php` | `src/Services/OpenApiServices.php` | KEEP | Exclude filter. |
| `openapi.middleware_security_map` | `config/openapi.php` | `src/Services/OpenApiServices.php` | KEEP | Mapea middleware → security. |
| `openapi.response_examples` | `config/openapi.php` | `src/Services/OpenApiServices.php` | KEEP | Respuestas por status. |
| `openapi.routes.*` | `config/openapi.php` | `src/routes/web.php` | KEEP | Configuración de rutas. |
| `openapi.exclude_modules` | `config/openapi.php` | `src/Services/OpenApiServices.php` | KEEP | Declarada para excluir módulos. |
| `openapi.exclude_module_routes` | `config/openapi.php` | `src/Services/OpenApiServices.php` | KEEP | Declarada para excluir rutas por módulo. |
| `openapi-docs.*` (`crud_templates`, `entities`, `custom_endpoints`, `auto_detect`, `field_descriptions`, `priority`) | `config/openapi-docs.php` | **No runtime usage** | NEEDS MANUAL CONFIRM | Config publishable, pero no leída. |
| `openapi-templates.enabled` | `config/openapi-templates.php` | **No runtime usage** | INVESTIGATE / REMOVE | No hay `config('openapi-templates.enabled')`. |
| `openapi-templates.paths` | `config/openapi-templates.php` | **No runtime usage** | INVESTIGATE / REMOVE | No hay `config('openapi-templates.paths')`. |
| `openapi-templates.generic_templates` | `config/openapi-templates.php` | **No runtime usage** | INVESTIGATE / REMOVE | No hay `config('openapi-templates.generic_templates')`. |
| `openapi-templates.query_builder` | `config/openapi-templates.php` | **No runtime usage** | INVESTIGATE / REMOVE | No hay `config('openapi-templates.query_builder')`. |
| `openapi-templates.auto_detect` | `config/openapi-templates.php` | **No runtime usage** | INVESTIGATE / REMOVE | No hay `config('openapi-templates.auto_detect')`. |
| `openapi-templates.rendering.debug` | `config/openapi-templates.php` | `src/Services/Documentation/TemplateDocumentationResolver.php` | KEEP | Debug renderer. |
| `openapi-templates.rendering.cache_enabled` | `config/openapi-templates.php` | `src/Services/Documentation/TemplateDocumentationResolver.php` | KEEP | Cache on/off. |
| `openapi-templates.rendering.validate_output` | `config/openapi-templates.php` | **No runtime usage** | INVESTIGATE / REMOVE | No hay `config('openapi-templates.rendering.validate_output')`. |
| `openapi-templates.rendering.cache_ttl` | `config/openapi-templates.php` | **No runtime usage** | INVESTIGATE / REMOVE | No hay `config('openapi-templates.rendering.cache_ttl')`. |
| `openapi-templates.entity_descriptions` | `config/openapi-templates.php` | `src/Services/Documentation/MetadataExtractor.php` | KEEP | Descripciones manuales. |
| `openapi-templates.filters` | `config/openapi-templates.php` | **No runtime usage** | INVESTIGATE / REMOVE | No hay `config('openapi-templates.filters')`. |
| `openapi-templates.validation` | `config/openapi-templates.php` | **No runtime usage** | INVESTIGATE / REMOVE | No hay `config('openapi-templates.validation')`. |
| `openapi-templates.examples` | `config/openapi-templates.php` | **No runtime usage** | INVESTIGATE / REMOVE | No hay `config('openapi-templates.examples')`. |
| `openapi-templates.performance` | `config/openapi-templates.php` | **No runtime usage** | INVESTIGATE / REMOVE | No hay `config('openapi-templates.performance')`. |
| `openapi-tests.templates` | `config/openapi-tests.php` | `src/Services/TestTemplateResolver.php` | KEEP | Templates por acción. |
| `openapi-tests.snippets` | `config/openapi-tests.php` | `src/Services/TestTemplateResolver.php` | KEEP | Snippets Postman/Insomnia. |
| `openapi-tests.custom_tests` | `config/openapi-tests.php` | `src/Services/TestTemplateResolver.php` | KEEP | Tests custom. |
| `openapi-tests.verbose_logging` | `config/openapi-tests.php` | **No runtime usage** | INVESTIGATE / REMOVE | No hay `config('openapi-tests.verbose_logging')`. |
| `openapi_templates.resource_descriptions` | **Missing (typo)** | `src/Services/OpenApiServices.php` | FIX KEY | Usa namespace inexistente. |
