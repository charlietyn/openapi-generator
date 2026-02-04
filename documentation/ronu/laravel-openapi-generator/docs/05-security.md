# Security

## Riesgos típicos y mitigaciones

### 1) Exposición de documentación en producción
Los endpoints HTTP permiten descargar la documentación. Asegura middleware y limita acceso en producción.

Evidence:
- File: config/openapi.php
  - Notes: `routes.enabled`, `routes.middleware`.
- File: src/routes/web.php
  - Notes: Rutas públicas si no hay middleware.

**Mitigación**:
- Configura `openapi.routes.middleware` con `auth`/`throttle`.

Evidence:
- File: config/openapi.php
  - Notes: `routes.middleware`.

### 2) Filtrado insuficiente de rutas
Rutas sensibles podrían entrar al spec si no se excluyen.

Evidence:
- File: config/openapi.php
  - Notes: `exclude_routes` con patrones iniciales.
- File: src/Services/OpenApiServices.php
  - Notes: Usa `exclude_routes` en `inspectRoutes()`.

**Mitigación**:
- Ajusta `exclude_routes` y valida rutas generadas.

Evidence:
- File: config/openapi.php
  - Notes: `exclude_routes`.

### 3) API Types mal configurados
Si `api_types` no refleja tu estructura real, se pueden omitir endpoints o exponer más de lo esperado.

Evidence:
- File: config/openapi.php
  - Notes: `api_types`.
- File: src/Services/OpenApiServices.php
  - Notes: Validación de API types.

### 4) Credenciales/secretos en ejemplos
El generador puede incluir ejemplos de tokens y placeholders; evita datos reales en config.

Evidence:
- File: src/Services/Documentation/MetadataExtractor.php
  - Notes: Ejemplos estáticos (`example_token`).
- File: config/openapi.php
  - Notes: Variables y placeholders en `environments`.

## Configuración segura recomendada

```php
// config/openapi.php
'routes' => [
    'enabled' => true,
    'prefix' => 'documentation',
    'middleware' => ['auth', 'throttle:60,1'],
],
```

Evidence:
- File: config/openapi.php
  - Notes: Estructura de `routes`.

## Unknown / To confirm

- No se detectan middlewares propios, guards, policies o eventos específicos en el paquete.

Evidence:
- File: src
  - Notes: No hay clases Middleware, Policies ni Events.

## Evidence
- File: src/routes/web.php
  - Notes: Endpoints HTTP expuestos.
