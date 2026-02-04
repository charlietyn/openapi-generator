# Artisan commands

## openapi:generate

Genera OpenAPI (JSON/YAML) y opcionalmente Postman/Insomnia.

**Signature**:
```
openapi:generate
    {--format=json : Output format (json or yaml)}
    {--output= : Output file path}
    {--no-cache : Disable cache}
    {--api-type=* : Filter by API type (api, site, mobile, admin)}
    {--all : Generate all formats for all channels (OpenAPI + Postman + Insomnia)}
    {--with-postman : Generate Postman collection}
    {--with-insomnia : Generate Insomnia workspace}
    {--environment=artisan : Environment to use (artisan, local, production)}
```

Evidence:
- File: src/Commands/GenerateOpenApiSpec.php
  - Notes: Firma del comando.

## Unknown / To confirm

- No se observan otros comandos registrados en el código actual.

Evidence:
- File: src/Providers/OpenApiGeneratorServiceProvider.php
  - Notes: Solo registra `GenerateOpenApiSpec`.
