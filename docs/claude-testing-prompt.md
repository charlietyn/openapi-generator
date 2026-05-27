# Prompt para Claude: Validación de generación OpenAPI/Insomnia/Postman desde `route:list`

Actúa como **Senior Laravel Architect + API DX Engineer** con foco en calidad de generación para **OpenAPI, Insomnia y Postman**, aplicando principios **KISS, SOLID y DRY**.

## Contexto
Estoy validando un generador de OpenAPI que además exporta colecciones para **Insomnia** y **Postman**.
Necesito que pruebes el comportamiento a partir de las rutas reales del proyecto Laravel.

## Proyecto base para la prueba
El proyecto Laravel base sobre el que debes ejecutar toda la validación está en:

`D:\www\kwikvet`

Asume que este proyecto **ya tiene instalada esta biblioteca en la versión actual implementada** (incluyendo los cambios recientes de generación y agrupación).

Antes de correr cualquier comando, muévete explícitamente a esa ruta de trabajo.

## Objetivo
Usa como fuente principal el resultado de:

```bash
php artisan route:list
```

y verifica que la generación cumple estas reglas funcionales:

1. No agrupar incorrectamente todo en un módulo genérico ambiguo (`general`) cuando eso cause colisión con un módulo real llamado igual.
2. Estructura jerárquica correcta por canal y dominio:
   - `apiType -> module -> entity -> relation (si aplica) -> endpoints`
3. En canal **admin**, endpoints no públicos deben llevar seguridad **Bearer** con variable de token (Insomnia `{{ _.token }}` o equivalente consistente en Postman).
4. No deben existir endpoints de documentación que apunten a raíz de módulo (ejemplo: `/admin/mod_clients` sin entidad funcional).
5. Naming técnico consistente por endpoint:
   - `admin.mod_clients.client.index`
   - `admin.mod_clients.client.show`
   - `admin.mod_clients.client.validate`
   - `admin.mod_clients.client.create`
   - `admin.mod_clients.client.update`
   - `admin.mod_clients.client.delete`
   - `admin.mod_clients.client.bulk`
   - `admin.mod_clients.client.bulk_update`
   - `admin.mod_clients.client.destroy`
   - `admin.mod_clients.client.delete-by-id`
   - `admin.mod_clients.client.export-excel`
   - `admin.mod_clients.client.export-pdf`
   - Para relaciones: `admin.mod_clients.client.appointments.index`, etc.
6. Confirmar si la documentación de ejemplo (templates/custom/generic o metadata inferida) se está reflejando correctamente en request bodies de OpenAPI y en exportación a Postman/Insomnia.

## Instrucciones de ejecución (paso a paso)
1. Ejecuta:
   ```bash
   php artisan route:list
   ```
2. Identifica rutas por canal (`admin`, `frontend`, `mobile`) y separa públicas vs protegidas por middleware.
3. Ejecuta el comando de generación OpenAPI del proyecto (detecta automáticamente el comando correcto; si hay varios, prueba al menos OpenAPI + Insomnia + Postman).
4. Inspecciona los artefactos generados (json/yaml de OpenAPI y colecciones exportadas).
5. Cruza cada endpoint generado contra `route:list` para detectar:
   - faltantes
   - duplicados
   - nombrado incorrecto
   - seguridad incorrecta
   - jerarquía de folders incorrecta
6. Valida ejemplos/documentación:
   - si hay `requestBody` específico por acción/entidad
   - si cayó a fallback genérico
   - posibles causas (template no encontrado, action mapping inconsistente, metadata incompleta)
7. Entrega diagnóstico y propuesta de corrección mínima con impacto controlado.

## Formato de salida requerido
Responde con esta estructura exacta:

### A) Resumen Ejecutivo
- Estado general: `PASS | PASS con observaciones | FAIL`
- Riesgos principales (máx 5 bullets)

### B) Evidencia de pruebas
- Comandos ejecutados
- Archivos generados inspeccionados
- Tabla de cobertura:
  - `Route (route:list)`
  - `OpenAPI path`
  - `Insomnia folder/request`
  - `Postman folder/request`
  - `Resultado`

### C) Validación por regla (1..6)
Para cada regla:
- `Estado: PASS/FAIL`
- `Evidencia concreta`
- `Impacto`
- `Fix recomendado (mínimo cambio)`

### D) Hallazgos técnicos profundos
- Problemas de diseño (naming, parser URI, seguridad, templates)
- Inconsistencias entre OpenAPI vs Insomnia vs Postman
- Casos borde (relations, bulk_update, delete-by-id, export-*)

### E) Plan de remediación priorizado
- **P0/P1/P2**
- archivo sugerido a tocar
- cambio propuesto
- riesgo de regresión
- prueba de validación posterior

### F) Checklist final de aceptación
Checklist con `[ ]` / `[x]` para cada regla funcional.

## Criterios de calidad
- No asumas: todo hallazgo debe tener evidencia.
- Si falta contexto, indica exactamente qué comando o archivo adicional necesitas.
- Prioriza precisión sobre verbosidad.
- Sé estricto con naming conventions y seguridad de admin.
