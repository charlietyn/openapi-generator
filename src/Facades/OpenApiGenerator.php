<?php

namespace Ronu\OpenApiGenerator\Facades;

use Illuminate\Support\Facades\Facade;
use Ronu\OpenApiGenerator\OpenApiGenerator as OpenApiGeneratorService;

class OpenApiGenerator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OpenApiGeneratorService::class;
    }
}
