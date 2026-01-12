<?php

namespace Ronu\OpenApiGenerator;

use Ronu\OpenApiGenerator\Services\OpenApiServices;

class OpenApiGenerator
{
    public function __construct(private readonly OpenApiServices $service)
    {
    }

    public function generate(
        bool $useCache = true,
        ?array $apiTypes = null,
        ?string $environment = null,
        string $format = 'openapi'
    ): array {
        return $this->service->generate($useCache, $apiTypes, $environment, $format);
    }

    public function generateOpenApi(
        bool $useCache = true,
        ?array $apiTypes = null,
        ?string $environment = null
    ): array {
        return $this->generate($useCache, $apiTypes, $environment, 'openapi');
    }

    public function generatePostman(
        bool $useCache = true,
        ?array $apiTypes = null,
        ?string $environment = null
    ): array {
        return $this->generate($useCache, $apiTypes, $environment, 'postman');
    }

    public function generateInsomnia(
        bool $useCache = true,
        ?array $apiTypes = null,
        ?string $environment = null
    ): array {
        return $this->generate($useCache, $apiTypes, $environment, 'insomnia');
    }
}
