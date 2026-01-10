<?php

namespace Ronu\OpenApiGenerator\Services\Documentation;

use Illuminate\Support\Facades\Log;

/**
 * Valid JSON Template Processor
 *
 * Processes 100% valid JSON templates with placeholder syntax.
 *
 * ARCHITECTURAL PRINCIPLE:
 * Templates must ALWAYS be valid JSON - before and after processing.
 * This ensures portability, validation, and editor compatibility.
 *
 * PLACEHOLDER SYNTAX:
 * - "__VAR:variable_name__"  - Simple variable (string, number, boolean, null)
 * - "__JSON:array_var__"      - Array/object (auto-converted to JSON)
 * - "__IF:condition__"        - Conditional start
 * - "__ENDIF:condition__"     - Conditional end
 * - "__EACH:items__"          - Loop start
 * - "__ITEM__"                - Current item in loop
 * - "__ENDEACH:items__"       - Loop end
 *
 * EXAMPLE TEMPLATE (100% valid JSON):
 * {
 *   "summary": "List __VAR:entity_plural__",
 *   "example": "__JSON:available_relations__"
 * }
 *
 * BENEFITS:
 * ✅ Always valid JSON (validates with jq, jsonlint, etc.)
 * ✅ No template engine dependencies
 * ✅ Editors don't show syntax errors
 * ✅ Portable to any programming language
 * ✅ Easy to understand and maintain
 * ✅ Can use standard JSON tools
 *
 * @package Ronu\OpenApiGenerator\Services\Documentation
 * @version 6.0.0 - Valid JSON Architecture
 * @author Designed for maximum portability
 */
class ValidJSONTemplateProcessor
{
    /**
     * Enable debug logging
     */
    protected bool $debug = false;

    /**
     * Placeholder patterns
     */
    protected array $patterns = [
        'var' => '/__VAR:(\w+)__/',
        'json' => '/__JSON:(\w+)__/',
        'if' => '/"__IF:(\w+)__"/',
        'endif' => '/"__ENDIF:(\w+)__"/',
        'each' => '/"__EACH:(\w+)__"/',
        'endeach' => '/"__ENDEACH:(\w+)__"/',
        'item' => '/"__ITEM__"/',
    ];

    /**
     * Constructor
     *
     * @param bool $debug Enable debug mode
     */
    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * Process template file with variables
     *
     * @param string $templatePath Path to JSON template file
     * @param array $variables Variables for replacement
     * @return array Processed JSON as associative array
     * @throws \Exception If template is invalid or processing fails
     */
    public function process(string $templatePath, array $variables): array
    {
        try {
            $this->log('info', "Processing template: " . basename($templatePath));

            // 1. Load template file
            if (!file_exists($templatePath)) {
                throw new \Exception("Template not found: {$templatePath}");
            }

            $content = file_get_contents($templatePath);

            if ($content === false) {
                throw new \Exception("Failed to read template: {$templatePath}");
            }

            // 2. Validate template is valid JSON BEFORE processing
            $this->validateJSON($content, "Template file {$templatePath}");

            // 3. Process template
            $processed = $this->processContent($content, $variables);
            $processed = str_replace('["{', '[{', $processed);
            $processed = str_replace(']"', ']', $processed);
            $processed = str_replace('"[{"', '[{"', $processed);
            $processed = str_replace('}"', '}', $processed);
            $processed = str_replace(': "{', ': {', $processed);
            $processed = str_replace(': "[]', ': "[]"', $processed);
            $processed = str_replace(',"{', ',{', $processed);
            // 4. Validate result is still valid JSON
            $this->validateJSON($processed, "Processed template {$templatePath}");

            // 5. Parse to array
            return json_decode($processed, true);
        } catch (\Exception $e) {
            Log::channel('render-template')->error('Template rendering failed ', [
                'template' => basename($templatePath),
                'entity' => $variables['entity'] ?? 'unknown',
                'action' => $variables['action'] ?? 'unknown'
            ]);
        }
        return json_decode("{}", true);
    }

    /**
     * Process inline template string
     *
     * @param string $template Template JSON string
     * @param array $variables Variables for replacement
     * @return array Processed JSON as associative array
     * @throws \Exception If template is invalid
     */
    public function processString(string $template, array $variables): array
    {
        // Validate template
        $this->validateJSON($template, "Template string");

        // Process
        $processed = $this->processContent($template, $variables);

        // Validate result
        $this->validateJSON($processed, "Processed string");

        return json_decode($processed, true);
    }

    /**
     * Process template content (core logic)
     *
     * @param string $content JSON content with placeholders
     * @param array $variables Variables for replacement
     * @return string Processed JSON string
     */
    protected function processContent(string $content, array $variables): string
    {
        // Order matters: conditionals → loops → variables

        // 1. Process conditionals
        $content = $this->processConditionals($content, $variables);

        // 2. Process loops
        $content = $this->processLoops($content, $variables);

        // 3. Process variables
        $content = $this->processVariables($content, $variables);

        return $content;
    }

    /**
     * Process conditional blocks: "__IF:var__" ... "__ENDIF:var__"
     *
     * @param string $content Content with conditionals
     * @param array $variables Variables
     * @return string Processed content
     */
    protected function processConditionals(string $content, array $variables): string
    {
        // Match: "__IF:variable__" ... "__ENDIF:variable__"
        $pattern = '/"__IF:(\w+)__"(.*?)"__ENDIF:\\1__"/s';

        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $varName = $matches[1];
            $innerContent = $matches[2];

            $this->log('debug', "Processing conditional: {$varName}");

            // Check if variable exists and is truthy
            $isTrue = isset($variables[$varName]) && !empty($variables[$varName]);

            if ($isTrue) {
                // Keep inner content, remove markers
                return $innerContent;
            }

            // Remove entire block (including commas)
            return '';
        }, $content);
    }

    /**
     * Process loop blocks: "__EACH:items__" ... "__ENDEACH:items__"
     *
     * @param string $content Content with loops
     * @param array $variables Variables
     * @return string Processed content
     */
    protected function processLoops(string $content, array $variables): string
    {
        // Match: "__EACH:variable__" ... "__ENDEACH:variable__"
        $pattern = '/"__EACH:(\w+)__"(.*?)"__ENDEACH:\\1__"/s';

        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $varName = $matches[1];
            $template = $matches[2];

            $this->log('debug', "Processing loop: {$varName}");

            if (!isset($variables[$varName]) || !is_array($variables[$varName])) {
                return '[]'; // Empty array
            }

            $items = $variables[$varName];
            $results = [];

            foreach ($items as $item) {
                $itemContent = $template;

                // Replace "__ITEM__" with current item
                if (is_array($item) || is_object($item)) {
                    $itemJson = json_encode($item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    $itemContent = str_replace('"__ITEM__"', $itemJson, $itemContent);
                } else {
                    $itemJson = json_encode($item);
                    $itemContent = str_replace('"__ITEM__"', $itemJson, $itemContent);
                }

                $results[] = $itemContent;
            }

            return '[' . implode(',', $results) . ']';
        }, $content);
    }

    /**
     * Process variable placeholders: "__VAR:name__" and "__JSON:name__"
     *
     * @param string $content Content with variable placeholders
     * @param array $variables Variables
     * @return string Processed content
     */
    protected function processVariables(string $content, array $variables): string
    {

        $this->log('info', "🔧 Processing variables. Total keys: " . count($variables));

        preg_match_all('/__VAR:([^_]+)__/', $content, $unreplacedVars);
        if (!empty($unreplacedVars[1])) {
            $this->log('warning', "⚠️ Found unreplaced __VAR__ before processing: " . implode(', ', array_unique($unreplacedVars[1])));
        }
        foreach ($variables as $key => $value) {
            // 1️⃣ Process __VAR:key__ (simple variables)
            $varPattern = '__VAR:' . preg_quote($key, '/') . '__';

            if (is_scalar($value) || $value === null) {
                $replacement = $this->escapeForJsonString($value);
                $content = str_replace($varPattern, $replacement, $content);

                $this->log('debug', "Replaced __VAR:{$key}__ with: " . substr($replacement, 0, 50));
            }

            // 2️⃣ Process __JSON:key__ (arrays/objects)
            $jsonPattern = '__JSON:' . preg_quote($key, '/') . '__';

            if (is_array($value) || is_object($value)) {
                // Para arrays/objects
                $replacement = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $content = str_replace($jsonPattern, $replacement, $content);

                $this->log('debug', "Replaced __JSON:{$key}__ with: " . substr($replacement, 0, 50));
            } elseif ($value === null) {
                $content = str_replace($jsonPattern, 'null', $content);
            }
        }

        preg_match_all('/__VAR:([^_]+)__/', $content, $stillUnreplaced);
        if (!empty($stillUnreplaced[1])) {
            $this->log('error', "❌ Still unreplaced __VAR__ after processing: " . implode(', ', array_unique($stillUnreplaced[1])));
        }

        return $content;
    }

    /**
     * Escape value for safe insertion in JSON string context
     */
    protected function escapeForJsonString($value): string
    {
        if ($value === null) {
            return '';
        }

        // Convert to string
        $value = (string)$value;

        // Escape special JSON characters
        $value = str_replace(
            ['\\', '"', "\n", "\r", "\t"],
            ['\\\\', '\\"', '\\n', '\\r', '\\t'],
            $value
        );

        return $value;
    }


    /**
     * Validate JSON string
     *
     * @param string $json JSON string
     * @param string $context Context for error message
     * @throws \Exception If JSON is invalid
     */
    protected function validateJSON(string $json, string $context): void
    {
        json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = json_last_error_msg();

            $this->log('error', "{$context} is not valid JSON: {$error}");

            throw new \Exception("{$context} is not valid JSON: {$error}");
        }

        $this->log('debug', "{$context} is valid JSON ✓");
    }

    /**
     * Validate template file
     *
     * @param string $templatePath Path to template
     * @return bool True if valid
     */
    public function validateTemplate(string $templatePath): bool
    {
        if (!file_exists($templatePath)) {
            return false;
        }

        $content = file_get_contents($templatePath);

        if ($content === false) {
            return false;
        }

        try {
            $this->validateJSON($content, "Template");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get placeholder syntax documentation
     *
     * @return array Syntax guide
     */
    public function getSyntaxGuide(): array
    {
        return [
            'Basic Variables' => [
                '__VAR:entity_name__' => 'Simple string variable',
                '__VAR:count__' => 'Number variable',
                '__VAR:is_active__' => 'Boolean variable',
            ],
            'JSON Variables' => [
                '__JSON:available_fields__' => 'Array variable (auto-converted to JSON)',
                '__JSON:model_schema__' => 'Object variable (auto-converted to JSON)',
            ],
            'Conditionals' => [
                '"__IF:has_relations__" ... "__ENDIF:has_relations__"' => 'Conditional block (shown if truthy)',
            ],
            'Loops' => [
                '"__EACH:items__" ... "__ENDEACH:items__"' => 'Loop through array',
                '__ITEM__' => 'Current item value in loop',
            ],
            'Complete Example' => [
                'template' => '{"name": "__VAR:entity__", "fields": "__JSON:fields__"}',
                'variables' => ['entity' => 'users', 'fields' => ['id', 'name']],
                'result' => '{"name": "users", "fields": ["id","name"]}',
            ],
        ];
    }

    /**
     * Get list of placeholders in template
     *
     * @param string $templatePath Path to template
     * @return array List of placeholders found
     */
    public function findPlaceholders(string $templatePath): array
    {
        if (!file_exists($templatePath)) {
            return [];
        }

        $content = file_get_contents($templatePath);
        $placeholders = [];

        // Find __VAR:xxx__
        if (preg_match_all('/__VAR:(\w+)__/', $content, $matches)) {
            $placeholders['variables'] = array_unique($matches[1]);
        }

        // Find __JSON:xxx__
        if (preg_match_all('/__JSON:(\w+)__/', $content, $matches)) {
            $placeholders['json'] = array_unique($matches[1]);
        }

        // Find __IF:xxx__
        if (preg_match_all('/__IF:(\w+)__/', $content, $matches)) {
            $placeholders['conditionals'] = array_unique($matches[1]);
        }

        // Find __EACH:xxx__
        if (preg_match_all('/__EACH:(\w+)__/', $content, $matches)) {
            $placeholders['loops'] = array_unique($matches[1]);
        }

        return $placeholders;
    }

    /**
     * Log message
     *
     * @param string $level Log level
     * @param string $message Message
     */
    protected function log(string $level, string $message): void
    {
        if ($this->debug || $level === 'error') {
            if (function_exists("Log::channel('openapi')->" . $level)) {
                Log::channel('openapi')->$level("[ValidJSONTemplateProcessor] {$message}");
            } else {
                error_log("[{$level}] [ValidJSONTemplateProcessor] {$message}");
            }
        }
    }
}