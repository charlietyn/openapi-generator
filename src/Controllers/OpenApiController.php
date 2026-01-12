<?php

namespace Ronu\OpenApiGenerator\Controllers;

use Illuminate\Routing\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Ronu\OpenApiGenerator\Services\EnvironmentGenerator;
use Ronu\OpenApiGenerator\Services\InsomniaWorkspaceGenerator;
use Ronu\OpenApiGenerator\Services\OpenApiServices;
use Ronu\OpenApiGenerator\Services\PostmanCollectionGenerator;
use Symfony\Component\Yaml\Yaml;

/**
 * OpenAPI Documentation Controller - Version 2
 *
 * Supports:
 * - Multiple formats (OpenAPI, Postman, Insomnia)
 * - API type filtering
 * - Environment selection
 * - Automatic file naming
 */
class OpenApiController extends Controller
{
    protected OpenApiServices $generator;
    protected EnvironmentGenerator $environmentGenerator;

    public function __construct(
        OpenApiServices $generator,
        EnvironmentGenerator $environmentGenerator
    ) {
        $this->generator = $generator;
        $this->environmentGenerator = $environmentGenerator;
    }

    /**
     * Generate and return OpenAPI specification
     *
     * Query Parameters:
     * - format: json|yaml (for OpenAPI format)
     * - api_type: api|site|movile (comma-separated for multiple)
     * - environment: artisan|local|production
     *
     * @param string $format Format (json, yaml, postman, insomnia)
     * @param Request $request
     * @return Response|JsonResponse
     */
    public function generate(Request $request,string $format = 'json'): Response|JsonResponse
    {
        // Validate format
        if (!in_array($format, ['json', 'yaml', 'yml', 'postman', 'insomnia'])) {
            return response()->json([
                'error' => 'Invalid format. Supported formats: json, yaml, postman, insomnia',
            ], 400);
        }

        try {
            // Parse query parameters
            $apiTypes = $this->parseApiTypes($request->query('api_type'));
            $environment = $request->query('environment', config('openapi.default_environment'));

            // Determine output format
            $outputFormat = $this->determineFormat($format);

            // Generate specification
            $spec = $this->generator->generate(
                useCache: true,
                apiTypes: $apiTypes,
                environment: $environment,
                format: $outputFormat
            );

            // Generate filename
            $filename = $this->generator->generateFilename($outputFormat, $apiTypes, $environment);

            // Return in appropriate format
            if (in_array($format, ['json', 'postman', 'insomnia'])) {
                return $this->returnJson($spec, $filename);
            }

            return $this->returnYaml($spec, $filename);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate specification',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    // AGREGAR después del método generate()

    /**
     * Generate Postman collection
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function postman(\Illuminate\Http\Request $request): \Illuminate\Http\Response
    {
        $apiTypes = $this->parseApiTypes($request->query('type'));
        $environment = $request->query('environment', 'artisan');

        $this->generator->setApiTypeFilter($apiTypes);
        $spec = $this->generator->generate();

        $postmanGen = app(PostmanCollectionGenerator::class);
        $collection = $postmanGen->generate($spec, $environment, $apiTypes);

        $filename = $this->buildFilename('postman-collection', $apiTypes);

        return response(json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 200)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Generate Insomnia workspace
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function insomnia(\Illuminate\Http\Request $request): \Illuminate\Http\Response
    {
        $apiTypes = $this->parseApiTypes($request->query('type'));
        $environment = $request->query('environment', 'artisan');

        $this->generator->setApiTypeFilter($apiTypes);
        $spec = $this->generator->generate();

        $insomniaGen = app(InsomniaWorkspaceGenerator::class);
        $workspace = $insomniaGen->generate($spec, $environment, $apiTypes);

        $filename = $this->buildFilename('insomnia-workspace', $apiTypes);

        return response(json_encode($workspace, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 200)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Get Postman environment file
     *
     * @param string $name Environment name
     * @return \Illuminate\Http\JsonResponse
     */
    public function postmanEnvironment(string $name): \Illuminate\Http\JsonResponse
    {
        $envGen = app(EnvironmentGenerator::class);

        if (!$envGen->isValidEnvironment($name)) {
            return response()->json([
                'error' => 'Invalid environment name',
                'available' => $envGen->getSubEnvironments(),
            ], 400);
        }

        $environment = $envGen->generatePostman($name);

        return response()->json($environment)
            ->header('Content-Disposition', "attachment; filename=\"postman-env-{$name}.json\"");
    }

    /**
     * Parse API types from query parameter
     *
     * @param string|null $typeParam
     * @return array
     */
    protected function parseApiTypes(?string $typeParam): array
    {
        if (!$typeParam) {
            return [];
        }

        $types = array_map('trim', explode(',', $typeParam));
        $validTypes = array_keys(config('openapi.api_types', []));
        $types = array_intersect($types, $validTypes);

        return array_values($types);
    }

    /**
     * Build filename based on API types
     *
     * @param string $base Base filename
     * @param array $apiTypes API types
     * @return string Filename
     */
    protected function buildFilename(string $base, array $apiTypes): string
    {
        if (empty($apiTypes)) {
            return "{$base}-all.json";
        }

        $suffix = implode('-', $apiTypes);
        return "{$base}-{$suffix}.json";
    }


    /**
     * Get environment configuration
     *
     * @param string $environment Environment name
     * @param string $format Format (postman or insomnia)
     * @return JsonResponse
     */
    public function environment(string $environment, string $format = 'postman'): JsonResponse
    {
        try {
            $env = $this->environmentGenerator->generate($environment, $format);

            return response()->json($env);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate environment',
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get all environments
     *
     * @param string $format Format (postman or insomnia)
     * @return JsonResponse
     */
    public function environments(string $format = 'postman'): JsonResponse
    {
        try {
            $environments = $this->environmentGenerator->getAllEnvironments($format);

            return response()->json([
                'environments' => $environments,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate environments',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear OpenAPI cache
     *
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        try {
            $this->generator->clearCache();

            return response()->json([
                'message' => 'OpenAPI cache cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to clear cache',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Determine output format
     *
     * @param string $format
     * @return string
     */
    protected function determineFormat(string $format): string
    {
        return match($format) {
            'postman' => 'postman',
            'insomnia' => 'insomnia',
            default => 'openapi',
        };
    }

    /**
     * Return specification as JSON
     *
     * @param array $spec
     * @param string $filename
     * @return Response
     */
    protected function returnJson(array $spec, string $filename): Response
    {
        $json = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return response($json, 200)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Return specification as YAML
     *
     * @param array $spec
     * @param string $filename
     * @return Response
     */
    protected function returnYaml(array $spec, string $filename): Response
    {
        $filename = str_replace('.json', '.yaml', $filename);
        $yaml = Yaml::dump($spec, 10, 2);

        return response($yaml, 200)
            ->header('Content-Type', 'application/x-yaml')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Show documentation UI (optional)
     *
     * @return \Illuminate\View\View
     */
    public function ui()
    {
        $specUrl = route('openapi.generate', ['format' => 'json']);

        return view('openapi::openapi.ui', compact('specUrl'));
    }

    /**
     * Show API information and available endpoints
     *
     * @return JsonResponse
     */
    public function info(): JsonResponse
    {
        $apiTypes = config('openapi.api_types');
        $environments = array_keys(config('openapi.environments'));

        return response()->json([
            'version' => config('openapi.info.version'),
            'title' => config('openapi.info.title'),
            'available_formats' => ['openapi', 'postman', 'insomnia'],
            'available_api_types' => array_keys($apiTypes),
            'available_environments' => $environments,
            'endpoints' => [
                'openapi_json' => route('openapi.generate', ['format' => 'json']),
                'openapi_yaml' => route('openapi.generate', ['format' => 'yaml']),
                'postman' => route('openapi.postman'),
                'insomnia' => route('openapi.insomnia'),
                'environments' => route('openapi.environments', ['format' => 'postman']),
            ],
            'example_usage' => [
                'Filter by API type' => route('openapi.generate', ['format' => 'json']) . '?api_type=api',
                'Multiple API types' => route('openapi.generate', ['format' => 'json']) . '?api_type=api,movile',
                'With environment' => route('openapi.generate', ['format' => 'json']) . '?environment=production',
                'Postman collection for mobile' => route('openapi.postman') . '?api_type=movile&environment=production',
            ],
        ]);
    }
}
