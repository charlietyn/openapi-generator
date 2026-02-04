# Contributing

## Guía rápida (según README del repositorio)

1) Fork y crea una rama.
2) Commit de cambios.
3) Abre PR.

Evidence:
- File: README.md
  - Notes: Sección “Contributing”.

## Setup de desarrollo

```bash
composer install
vendor/bin/phpunit
```

Evidence:
- File: README.md
  - Notes: Sección “Development Setup”.
- File: composer.json
  - Notes: Dependencias de desarrollo.

## Code style / static analysis

```bash
vendor/bin/php-cs-fixer fix
vendor/bin/phpstan analyse
```

Evidence:
- File: README.md
  - Notes: Sección “Code Style” y “Static Analysis”.
- File: composer.json
  - Notes: Script `analyse`.

## Unknown / To confirm

- No hay archivo `CONTRIBUTING.md` en el repositorio.

Evidence:
- File: README.md
  - Notes: Instrucciones en README.
