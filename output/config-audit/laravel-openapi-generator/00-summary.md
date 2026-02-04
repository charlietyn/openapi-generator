# 00-summary.md

## Resumen ejecutivo
La auditoría identifica variables ENV sin efecto real, claves de configuración declaradas pero no consumidas, y referencias de configuración rotas. También hay uso de `env()` en runtime (fuera de `config/`), lo que puede romper `config:cache` en producción. Se proponen parches de bajo riesgo para alinear documentación y declarar claves faltantes en `config/openapi.php`, además de un plan gradual para resolver deuda de configuración sin romper compatibilidad.

## Métricas
- ENV declaradas: 17
- ENV usadas con efecto real: 13
- ENV sin uso efectivo (config no leído): 4
- ENV usadas en runtime pero no documentadas: 0
- Typos/Mismatch: 1
- Dead config keys (declaradas pero nunca leídas): 15

## Riesgos y recomendaciones
- **Alto:** Uso de `env()` en runtime (`src/Helpers/PlaceholderHelper.php`, `src/Services/EnvironmentGenerator.php`, `src/Services/OpenApiServices.php`). En producción con `config:cache`, esto puede romper o ignorar cambios esperados. Migrar a `config()` y documentar el flujo. 
- **Medio:** `config('openapi_templates.resource_descriptions')` apunta a un namespace inexistente. Esto bloquea la funcionalidad y puede confundir a usuarios. 
- **Medio:** `openapi-docs.php` está documentado y publishable, pero no se lee en runtime (posible configuración muerta). Confirmar intención antes de eliminar.
- **Bajo:** Rutas OpenAPI habilitadas por defecto sin middleware (revisar hardening en producción).

## Recomendación clave
El PR actual ya declara `openapi.exclude_modules`/`openapi.exclude_module_routes` y actualiza docs para evitar `PRODUCTION_URL`. El siguiente paso es migrar los usos de `env()` en runtime hacia `config()` y consolidar defaults en `config/`.
