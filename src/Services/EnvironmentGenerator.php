<?php

namespace Ronu\OpenApiGenerator\Services;


use Carbon\Carbon;
use Illuminate\Support\Str;
use Ronu\OpenApiGenerator\Helpers\PlaceholderHelper;

/**
 * Environment Generator Service
 *
 * Generates environment configurations for Postman and Insomnia.
 * Supports hierarchical environments with parent-child inheritance.
 *
 * @package Ronu\OpenApiGenerator\Services
 */
class EnvironmentGenerator
{
    protected string $title;

    /**
     * Generate Postman environment file
     *
     * @param string $environmentName Environment name (artisan, local, production)
     * @return array Postman environment structure
     */
    public function generatePostman(string $environmentName): array
    {
        $config = $this->getEnvironmentConfig($environmentName);

        return [
            'id' => uniqid('postman-env-'),
            'name' => $config['name'],
            'values' => $this->buildPostmanValues($config),
            '_postman_variable_scope' => 'environment',
            '_postman_exported_at' => Carbon::now()->toIso8601String(),
            '_postman_exported_using' => 'Laravel OpenAPI Generator v2.0',
        ];
    }

    public function generateApiKey(): string
    {
        return 'app_' . Str::random(40);
    }

    protected function buildSubEnvironmentData(string $environmentName): array
    {
        $data = [];

        switch ($environmentName) {
            case 'artisan':
                $data['base_url'] = 'http://127.0.0.1:8000';
                $data['token'] = '__TOKEN_TEMPLATE_PLACEHOLDER__';
                $data['api_key'] = $this->generateApiKey();
                break;

            case 'local':
                $data['base_url'] = PlaceholderHelper::replace('http://localhost/'.$this->title.'/public');
                $data['token'] = '__TOKEN_TEMPLATE_PLACEHOLDER__';
                $data['api_key'] = '';
                break;

            case 'production':
                $data['base_url'] = PlaceholderHelper::replace('http://'.$this->title.'.com');
                $data['token'] = '__TOKEN_TEMPLATE_PLACEHOLDER__';
                $data['api_key'] = '';
                break;

            default:
                // Fallback para otros environments
                $data['base_url'] = env('APP_URL', 'http://localhost:8000');
                $data['token'] = '__TOKEN_TEMPLATE_PLACEHOLDER__';
                $data['api_key'] = '';
        }

        return $data;
    }

    protected function buildBaseEnvironmentData(): array
    {
        return [
            'base_url' => env('APP_URL', 'http://localhost:8000'),
            'token' => '',
            'api_key' => '',
        ];
    }

    /**
     * Generate Insomnia environment (as part of workspace)
     *
     * @param string $environmentName Environment name
     * @param string $parentId Parent environment ID (for inheritance)
     * @return array Insomnia environment resource
     */
    public function generateInsomnia(string $environmentName, string $parentId): array
    {
        $config = $this->getEnvironmentConfig($environmentName);
        $isBase = $environmentName === 'base';
        $data = $isBase
            ? $this->buildBaseEnvironmentData()
            : $this->buildSubEnvironmentData($environmentName);

        return [
            '_id' => 'env_' . $environmentName . '_' . uniqid(),
            '_type' => 'environment',
            'parentId' => $parentId,
            'name' => $config['name'],
            'data' => $data,
        ];
    }

    /**
     * Get environment configuration with validation
     *
     * @param string $environmentName Environment name
     * @return array Environment configuration
     * @throws \InvalidArgumentException
     */
    protected function getEnvironmentConfig(string $environmentName): array
    {
        $envConfig = config("openapi.environments.{$environmentName}");

        if (!$envConfig) {
            throw new \InvalidArgumentException(
                "Environment '{$environmentName}' not found in config/openapi.php"
            );
        }

        return $envConfig;
    }

    /**
     * Build Postman values array
     *
     * @param array $config Environment configuration
     * @return array Array of Postman variable objects
     */
    protected function buildPostmanValues(array $config): array
    {
        $values = [];
        $allVars = $this->mergeVariables($config);

        foreach ($allVars as $key => $value) {
            $values[] = [
                'key' => $key,
                'value' => $value,
                'type' => 'default',
                'enabled' => true,
            ];
        }

        return $values;
    }


    protected function getTokenTemplate(): string
    {
        return '__TOKEN_TEMPLATE_PLACEHOLDER__';
    }


    /**
     * Merge variables with parent (if exists)
     *
     * @param array $config Environment configuration
     * @return array Merged variables
     */
    protected function mergeVariables(array $config): array
    {
        $variables = [];

        // If has parent, inherit variables
        if (isset($config['parent'])) {
            $parentConfig = config("openapi.environments.{$config['parent']}");

            if ($parentConfig) {
                // Inherit from parent
                $variables = array_merge(
                    $parentConfig['variables'] ?? [],
                    $parentConfig['tracking_variables'] ?? []
                );
            }
        }

        // Add base variables (if this is base environment)
        if (!isset($config['parent'])) {
            $variables = array_merge(
                $variables,
                $config['variables'] ?? [],
                $config['tracking_variables'] ?? []
            );
        } else {
            // Override with own variables
            $variables = array_merge($variables, $config['variables'] ?? []);
        }

        return $variables;
    }

    /**
     * Get all available environment names
     *
     * @return array Array of environment names
     */
    public function getAvailableEnvironments(): array
    {
        $environments = config('openapi.environments', []);
        return array_keys($environments);
    }

    /**
     * Get environments excluding base
     *
     * @return array Array of environment names (without 'base')
     */
    public function getSubEnvironments(): array
    {
        $environments = $this->getAvailableEnvironments();
        return array_filter($environments, fn($env) => $env !== 'base');
    }

    /**
     * Validate environment name
     *
     * @param string $environmentName Environment name
     * @return bool
     */
    public function isValidEnvironment(string $environmentName): bool
    {
        $environments = $this->getAvailableEnvironments();
        return in_array($environmentName, $environments);
    }

    /**
     * Get tracking variables
     *
     * @return array Tracking variables from base environment
     */
    public function getTrackingVariables(): array
    {
        return config('openapi.environments.base.tracking_variables', []);
    }

    /**
     * Generate all Postman environment files
     *
     * @return array Array of Postman environments
     */
    public function generateAllPostmanEnvironments(): array
    {
        $environments = [];
        $subEnvs = $this->getSubEnvironments();

        foreach ($subEnvs as $envName) {
            $environments[$envName] = $this->generatePostman($envName);
        }

        return $environments;
    }

    /**
     * Generate all Insomnia environments (for workspace)
     *
     * @param string $workspaceId Workspace ID
     * @return array Array of Insomnia environment resources
     */
    public function generateAllInsomniaEnvironments(string $workspaceId): array
    {
        $environments = [];

        // 1. Base environment
        $baseId = 'env_base_' . uniqid();
        $environments[] = $this->generateInsomnia('base', $workspaceId);
        $environments[0]['_id'] = $baseId; // Override with our ID

        // 2. Sub-environments (inherit from base)
        $subEnvs = $this->getSubEnvironments();

        foreach ($subEnvs as $envName) {
            $environments[] = $this->generateInsomnia($envName, $baseId);
        }

        return $environments;
    }
}