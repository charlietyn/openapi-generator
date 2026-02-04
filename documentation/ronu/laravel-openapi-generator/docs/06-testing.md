# Testing

## Qué pruebas existen

- **Feature tests** para rutas y generación básica.

Evidence:
- File: tests/Feature/OpenApiGeneratorTest.php
  - Notes: Tests de rutas y generación.

## Cómo correrlas

```bash
composer test
```

o

```bash
vendor/bin/phpunit
```

Evidence:
- File: composer.json
  - Notes: Script `test`.
- File: phpunit.xml
  - Notes: Configuración de PHPUnit.

## Enfoque de pruebas

- Verifica registro del service provider.
- Verifica rutas HTTP disponibles.
- Verifica salida JSON del endpoint OpenAPI.

Evidence:
- File: tests/Feature/OpenApiGeneratorTest.php
  - Notes: Métodos `test_service_provider_registers_openapi_service`, `test_routes_are_registered`, `test_spec_generation_endpoint_returns_json`.

## Unknown / To confirm

- No hay pruebas para templates JSON o caching en el test suite actual.

Evidence:
- File: tests/Feature/OpenApiGeneratorTest.php
  - Notes: Cobertura limitada a rutas/servicios básicos.

## Evidence
- File: tests/Feature/OpenApiGeneratorTest.php
  - Notes: Suite principal de tests.
