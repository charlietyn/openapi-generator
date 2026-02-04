# 04-action-plan.md

## Plan por etapas

### PR1 (Quick wins, bajo riesgo) ✅ (aplicado en este PR)
- Actualizar ejemplos en docs para eliminar `PRODUCTION_URL` como variable sugerida.
- Declarar `openapi.exclude_modules` y `openapi.exclude_module_routes` en `config/openapi.php` con defaults seguros.
- Añadir nota de seguridad: rutas OpenAPI habilitadas por defecto → recomendar middleware.

### PR2 (Riesgo medio)
- Resolver typo `openapi_templates.*` y definir dónde vive `resource_descriptions` (probable `openapi-templates`).
- Evaluar `openapi-templates.*` sin uso (enable/paths/query_builder/etc.) y decidir si implementar o deprecate.
- Evaluar `openapi-tests.verbose_logging` y eliminar si no se necesita.

### PR3 (Riesgo alto)
- Migrar `env()` en runtime a `config()` para soportar `config:cache`.
- Deprecar/remover `config/openapi-docs.php` si no se integra en runtime.

## Checklist de verificación
- [ ] `composer test` (o `vendor/bin/phpunit`)
- [ ] `composer analyse` (phpstan) si aplica
- [ ] `php artisan config:clear && php artisan config:cache` en proyecto host (manual)

## Estrategia de rollback
- Revertir el commit del PR específico.
- Restaurar config publicada desde `vendor:publish` si se modificó.
- Mantener backup del archivo original en cada PR.
