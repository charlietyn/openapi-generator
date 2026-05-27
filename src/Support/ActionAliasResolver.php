<?php

namespace Ronu\OpenApiGenerator\Support;

class ActionAliasResolver
{
    private const ACTION_ALIASES = [
        'index' => 'list',
        'store' => 'create',
        'edit' => 'update',
        'destroy' => 'delete',
        'update-multiple' => 'bulk_update',
    ];

    public static function normalize(string $action): string
    {
        return self::ACTION_ALIASES[$action] ?? $action;
    }

    public static function aliasesFor(string $action): array
    {
        $normalized = self::normalize($action);

        return $normalized === $action ? [] : [[$action => $normalized]];
    }
}
