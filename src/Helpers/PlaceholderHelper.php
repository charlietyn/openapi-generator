<?php

namespace Ronu\OpenApiGenerator\Helpers;

use Illuminate\Support\Str;

/**
 * Placeholder Helper
 *
 * Maneja el reemplazo dinámico de placeholders como ${{projectName}}
 * con valores extraídos del entorno (APP_NAME).
 *
 * @package App\Utils
 * @version 1.0.0
 */
class PlaceholderHelper
{
    /**
     * Reemplaza placeholders ${{projectName}} con el nombre real del proyecto
     *
     * @param string|array $content Contenido a procesar
     * @return string|array Contenido procesado
     */
    public static function replace($content)
    {
        $proyecto = self::getProjectName();
        
        if (is_array($content)) {
            return self::replaceInArray($content, $proyecto);
        }
        
        return self::replaceInString($content, $proyecto);
    }
    
    /**
     * Obtiene el nombre del proyecto desde APP_NAME
     *
     * Limpia el nombre para que sea válido en URLs y emails:
     * - Convierte a minúsculas
     * - Elimina espacios y caracteres especiales
     * - Mantiene solo letras, números, guiones y guiones bajos
     *
     * @return string Nombre del proyecto limpio
     */
    public static function getProjectName(): string
    {
        $appName = env('APP_NAME', 'proyecto');
        
        // Limpiar el nombre
        $clean = strtolower($appName);
        
        // Reemplazar espacios por guiones
        $clean = str_replace(' ', '-', $clean);
        
        // Eliminar caracteres especiales, mantener solo: a-z, 0-9, -, _
        $clean = preg_replace('/[^a-z0-9\-_]/', '', $clean);
        
        // Eliminar guiones/underscores múltiples
        $clean = preg_replace('/[\-_]+/', '-', $clean);
        
        // Eliminar guiones al inicio y final
        $clean = trim($clean, '-_');
        
        return $clean ?: 'proyecto';
    }
    
    /**
     * Reemplaza placeholders en un string
     *
     * @param string $content Contenido
     * @param string $proyecto Nombre del proyecto
     * @return string Contenido procesado
     */
    protected static function replaceInString(string $content, string $proyecto): string
    {
        return str_replace('${{projectName}}', $proyecto, $content);
    }
    
    /**
     * Reemplaza recursivamente placeholders en arrays
     *
     * @param array $content Array a procesar
     * @param string $proyecto Nombre del proyecto
     * @return array Array procesado
     */
    protected static function replaceInArray(array $content, string $proyecto): array
    {
        foreach ($content as $key => $value) {
            if (is_string($value)) {
                $content[$key] = self::replaceInString($value, $proyecto);
            } elseif (is_array($value)) {
                $content[$key] = self::replaceInArray($value, $proyecto);
            }
        }
        
        return $content;
    }
    
    /**
     * Genera URL completa reemplazando placeholders
     *
     * @param string $template Template de URL (ej: "https://${{projectName}}.com")
     * @return string URL procesada
     */
    public static function generateUrl(string $template): string
    {
        return self::replace($template);
    }
    
    /**
     * Genera email reemplazando placeholders
     *
     * @param string $prefix Prefijo del email (ej: "support")
     * @return string Email completo (ej: "support@proyecto.com")
     */
    public static function generateEmail(string $prefix = 'support'): string
    {
        $proyecto = self::getProjectName();
        return "{$prefix}@{$proyecto}.com";
    }
    
    /**
     * Genera múltiples URLs desde templates
     *
     * @param array $templates Array de templates
     * @return array Array de URLs procesadas
     */
    public static function generateUrls(array $templates): array
    {
        return array_map(fn($template) => self::generateUrl($template), $templates);
    }
}
