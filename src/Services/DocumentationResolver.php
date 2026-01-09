<?php
namespace Ronu\OpenApiGenerator\Services;

use Ronu\OpenApiGenerator\Services\Documentation\TemplateDocumentationResolver;

/**
 * Class DocumentationResolver
 *
 * Responsibility:
 *  - Acts as a façade/orchestrator to resolve documentation metadata for a given API operation.
 *  - Determines the "module" context (e.g., security, catalog) based on the controller namespace.
 *  - Delegates the final documentation resolution to TemplateDocumentationResolver.
 *
 * Typical usage:
 *  - Called during OpenAPI/Swagger generation when building operation docs (summary, description,
 *    request/response examples, tags, etc.) for a specific entity/action pair.
 */
class DocumentationResolver
{
    /**
     * @var TemplateDocumentationResolver $templateResolver
     *
     * Purpose:
     *  - Dependency used to apply template-based rules to produce documentation for an operation.
     *  - Encapsulates the actual mapping logic and templates (entity/action/module/controller → docs array).
     *
     * Notes:
     *  - Stored as a typed property for clarity and IDE/static analysis support.
     *  - In Laravel, you may also inject this via the service container instead of instantiating manually.
     */
    protected TemplateDocumentationResolver $templateResolver;

    /**
     * DocumentationResolver constructor.
     *
     * Behavior:
     *  - Instantiates the TemplateDocumentationResolver dependency.
     *
     * Design note:
     *  - This is manual instantiation (new ...). If you want better testability and DI alignment,
     *    prefer constructor injection:
     *      public function __construct(TemplateDocumentationResolver $templateResolver) { ... }
     */
    public function __construct()
    {
        $this->templateResolver = new TemplateDocumentationResolver();
    }

    /**
     * Resolve documentation data for a specific operation.
     *
     * @param string $entity Entity name
     * @param string $action Action name
     * @param string|null $controller Controller FQCN
     * @param mixed|null $route Laravel Route object (for scenario detection) ← NUEVO
     * @return array Documentation payload
     */
    public function resolveForOperation(
        string  $entity,
        string  $action,
        ?string $controller = null,
        mixed   $route = null
    ): array {
        // Determine module name from controller namespace
        $module = $this->extractModuleFromController($controller);

        // Delegate to template-based resolver
        return $this->templateResolver->resolveForOperation(
            $entity,
            $action,
            $module,
            $controller,
            $route
        );
    }

    /**
     * Extract the module name from a controller namespace/class string.
     *
     * Strategy:
     *  - If controller is null → returns "general".
     *  - Otherwise attempts to parse the module from a namespace like:
     *      Modules\\security\\Http\\Controllers\\UsersController
     *    and returns "security".
     *
     * Implementation details:
     *  - Uses regex: /Modules\\\\([^\\\\]+)/
     *    This captures the first namespace segment after "Modules\\".
     *
     * @param string|null $controller
     *   Fully-qualified controller name or null.
     *
     * @return string
     *   The detected module name (e.g., "security"), or "general" if not detected.
     *
     * Edge cases:
     *  - If the controller does not match the expected modular namespace convention,
     *    "general" is returned.
     */
    protected function extractModuleFromController(?string $controller): string
    {
        if (!$controller) {
            return 'general';
        }

        // Extract module from: Modules\security\Http\Controllers\UsersController
        if (preg_match('/Modules\\\\([^\\\\]+)/', $controller, $matches)) {
            return $matches[1];
        }

        return 'general';
    }
}
