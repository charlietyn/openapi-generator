<?php

namespace Ronu\OpenApiGenerator\Services;

use Illuminate\Support\Str;

/**
 * Test Template Resolver Service
 *
 * Generates test scripts for Postman and Insomnia based on templates.
 * Supports both CRUD actions and custom endpoints.
 *
 * @package Ronu\OpenApiGenerator\Services
 */
class TestTemplateResolver
{
    /**
     * Generate test script for an operation
     *
     * @param string $action Action name (e.g., 'index', 'store')
     * @param string $entity Entity name (e.g., 'users')
     * @param string $format Output format ('insomnia' or 'postman')
     * @param string|null $customTemplate Custom template key
     * @return array Array of test script lines
     */
    public function generateTestScript(
        string $action,
        string $entity,
        string $format = 'insomnia',
        ?string $customTemplate = null
    ): array {
        // Use custom template if provided
        if ($customTemplate) {
            return $this->loadCustomTemplate($customTemplate, $format, $entity);
        }

        // Get template for action
        $template = $this->getTemplateForAction($action, $entity);

        // Build test script
        return $this->buildTestScript($template, $entity, $format);
    }

    /**
     * Get template for action
     *
     * @param string $action Action name
     * @param string $entity Entity name
     * @return array
     */
    protected function getTemplateForAction(string $action, string $entity): array
    {
        // Check for custom endpoint test
        $customKey = "{$entity}.{$action}";
        if ($custom = config("openapi-tests.custom_tests.{$customKey}")) {
            return $custom;
        }

        // Use standard template
        return config("openapi-tests.templates.{$action}", [
            'checks' => ['status_200', 'json_response'],
        ]);
    }

    /**
     * Build test script from template
     *
     * @param array $template Template configuration
     * @param string $entity Entity name
     * @param string $format Output format
     * @return array
     */
    protected function buildTestScript(array $template, string $entity, string $format): array
    {
        $script = [];
        $checks = $template['checks'] ?? [];

        foreach ($checks as $check) {
            $checkCode = $this->getCheckCode($check, $entity, $format);
            $script = array_merge($script, $checkCode);
        }

        return $script;
    }

    /**
     * Get code for a specific check
     *
     * @param string $check Check name
     * @param string $entity Entity name
     * @param string $format Output format
     * @return array
     */
    protected function getCheckCode(string $check, string $entity, string $format): array
    {
        $snippet = config("openapi-tests.snippets.{$check}.{$format}", []);

        if (empty($snippet)) {
            return [];
        }

        // Replace placeholders
        $trackingVar = 'last_' . Str::snake($entity) . '_id';
        $entityName = ucfirst(Str::singular($entity));

        $replacements = [
            '{{tracking_var}}' => $trackingVar,
            '{{entity}}' => $entityName,
        ];

        return array_map(function ($line) use ($replacements) {
            return str_replace(
                array_keys($replacements),
                array_values($replacements),
                $line
            );
        }, $snippet);
    }

    /**
     * Load custom template
     *
     * @param string $templateKey Template key
     * @param string $format Output format
     * @param string $entity Entity name
     * @return array
     */
    protected function loadCustomTemplate(string $templateKey, string $format, string $entity): array
    {
        $customTest = config("openapi-tests.custom_tests.{$templateKey}");

        if (!$customTest) {
            return $this->buildTestScript(['checks' => ['status_200', 'json_response']], $entity, $format);
        }

        return $this->buildTestScript($customTest, $entity, $format);
    }

    /**
     * Check if entity has tracking variable
     *
     * @param string $entity Entity name
     * @return bool
     */
    public function hasTrackingVariable(string $entity): bool
    {
        $trackingVars = config('openapi.environments.base.tracking_variables', []);
        $trackingVarName = 'last_' . Str::snake($entity) . '_id';

        return array_key_exists($trackingVarName, $trackingVars);
    }

    /**
     * Get tracking variable name for entity
     *
     * @param string $entity Entity name
     * @return string|null
     */
    public function getTrackingVariableName(string $entity): ?string
    {
        if (!$this->hasTrackingVariable($entity)) {
            return null;
        }

        return 'last_' . Str::snake($entity) . '_id';
    }

    /**
     * Generate Postman test script
     *
     * @param string $action Action name
     * @param string $entity Entity name
     * @return array
     */
    public function generatePostmanTest(string $action, string $entity): array
    {
        return $this->generateTestScript($action, $entity, 'postman');
    }

    /**
     * Generate Insomnia test script
     *
     * @param string $action Action name
     * @param string $entity Entity name
     * @return array
     */
    public function generateInsomniaTest(string $action, string $entity): array
    {
        return $this->generateTestScript($action, $entity, 'insomnia');
    }
}