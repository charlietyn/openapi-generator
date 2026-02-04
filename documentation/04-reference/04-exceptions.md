# Exceptions

The package raises exceptions primarily for invalid input or template parsing issues.

## Common exception cases

- **Invalid API types**: `InvalidArgumentException` when a requested API type is unknown or disabled.
- **Invalid environment**: `InvalidArgumentException` when an environment key does not exist in `openapi.environments`.
- **Invalid templates**: `Exception` thrown when JSON templates are missing or invalid.

**Next:** [Security](../05-quality/00-security.md) • [Docs index](../index.md)

## Evidence
- File: src/Services/OpenApiServices.php
  - Symbol: OpenApiServices::validateApiTypes()
  - Notes: Throws `InvalidArgumentException` for invalid API types.
- File: src/Services/EnvironmentGenerator.php
  - Symbol: EnvironmentGenerator::getEnvironmentConfig()
  - Notes: Throws `InvalidArgumentException` when environment is missing.
- File: src/Services/Documentation/ValidJSONTemplateProcessor.php
  - Symbol: ValidJSONTemplateProcessor::loadTemplate()
  - Notes: Throws generic exceptions for missing/invalid templates.
- File: src/Controllers/OpenApiController.php
  - Symbol: OpenApiController::generate()
  - Notes: Catches `InvalidArgumentException` and returns 422.
