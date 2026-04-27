<?php

namespace RiseTechApps\CodeGenerate;

use Illuminate\Support\Facades\Facade;
use RiseTechApps\CodeGenerate\DTO\CodeConfig;
use RiseTechApps\CodeGenerate\DTO\CodeGenerateResult;

/**
 * @method static CodeGenerateResult generate(\Illuminate\Database\Eloquent\Model|string $class, ?CodeConfig $config = null)
 * @method static CodeConfig getConfigFromModel(\Illuminate\Database\Eloquent\Model $model)
 * @method static void clearSchemaCache(string $table, string $field = 'code')
 * @method static void clearAllCache()
 * @method static self setMaxCollisionAttempts(int $attempts)
 *
 * @see \RiseTechApps\CodeGenerate\CodeGenerate
 */
class CodeGenerateFacade extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'code-generate';
    }
}
