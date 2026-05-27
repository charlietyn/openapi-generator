# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.7] - 2026-05-27

### Fixed
- `isNwidartModule()` now matches snake_case module directories (e.g. `mod_clients`),
  not only StudlyCase, fixing module/entity/action parsing for such projects.
- `isModuleRootRoute()` uses structural `mod_*` detection and counts original URI
  segments, so instance routes like `/admin/mod_clients/{id}` are no longer dropped;
  restored `shouldExcludeBeforeProcess()` gated by `exclude_prefix_module_roots`.
- Admin endpoints secured by custom channel middleware now receive an implicit
  `BearerAuth` scheme (`force_admin_bearer_auth` + `admin_public_patterns`).
- Request bodies declared in custom templates (e.g. `auth.login`) are honoured
  before the generic `{ data }` fallback.

### Added
- Relation sub-resource support: `/{entity}/{parent}/{relation}` routes emit
  `x-relation` and `module.entity.relation.action` operationIds, with verb-aware
  actions (list/create/show/update/delete and bulk variants).
- Globally unique operationIds via `ensureUniqueOperationId()`.
- Postman/Insomnia exporters group relation requests into dedicated sub-folders.

## [1.0.0] - 2025-01-04

### Added
- Initial release
- Automatic metadata extraction from FormRequests and Models
- 4-strategy cascade for robust rule extraction
- Smart description generation
- Realistic example generation
- Multi-format export (OpenAPI, Postman, Insomnia)
- Template system for customization
- Quality validation with cebe/php-openapi
- Support for Nwidart modules
- Comprehensive configuration system

### Features
- 99% automatic documentation generation
- Contextual descriptions
- Factory-based examples
- Multiple API type support
- Middleware to security mapping
- Scenario detection
- Soft delete detection
- Relation extraction
