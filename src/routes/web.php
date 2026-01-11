<?php

use Illuminate\Support\Facades\Route;
use Ronu\OpenApiGenerator\Controllers\OpenApiController;
use \Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| OpenAPI Documentation Routes
|--------------------------------------------------------------------------
|
| These routes provide HTTP access to generated API documentation
|
| Configuration:
| - Enable/disable: config('openapi.routes.enabled')
| - Prefix: config('openapi.routes.prefix')
| - Middleware: config('openapi.routes.middleware')
|
*/

$prefix = config('openapi.routes.prefix', 'documentation');
$middleware = config('openapi.routes.middleware', []);

Route::prefix($prefix)
    ->middleware($middleware)
    ->name('openapi.')
    ->group(function () {

        // OpenAPI Specification (JSON or YAML)
        Route::get('openapi.{format}', [OpenApiController::class, 'generate'])
            ->where('format', 'json|yaml|yml')
            ->name('spec');

        // Postman Collection
        Route::get('postman', function (Request $request) {
            return app(OpenApiController::class)->generate($request, 'postman');
        })->name('postman');

        // Insomnia Workspace
        Route::get('insomnia', function (Request $request) {
            return app(OpenApiController::class)->generate($request, 'insomnia');
        })->name('insomnia');
    });