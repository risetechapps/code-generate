<?php

namespace RiseTechApps\CodeGenerate;

use Exception;
use Illuminate\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RiseTechApps\CodeGenerate\Database\DatabaseDriverFactory;
use RiseTechApps\CodeGenerate\DTO\CodeConfig;
use RiseTechApps\CodeGenerate\DTO\CodeGenerateResult;

class CodeGenerate
{
    public const DEFAULT_LENGTH = 4;
    public const DEFAULT_PREFIX = '';
    public const DEFAULT_FIELD = 'code';

    private ?Repository $cache = null;
    private int $maxCollisionAttempts = 5;

    public function __construct()
    {
        if (config('code-generate.cache_schema', false)) {
            $this->cache = Cache::store(config('code-generate.cache_store'));
        }
    }

    /**
     * Gera um código sequencial para o model especificado.
     *
     * @throws Exception
     */
    public static function generate(
        Model|string $class,
        ?CodeConfig $config = null
    ): CodeGenerateResult {
        $instance = app(self::class);

        if ($class instanceof Model) {
            $model = $class;
            $config ??= $instance->getConfigFromModel($model);
        } elseif (is_string($class)) {
            $model = new $class();
            if (!$model instanceof Model) {
                throw new Exception('Provided class must extend ' . Model::class);
            }
            $config ??= $instance->getConfigFromModel($model);
        } else {
            throw new Exception('CodeGenerate::generate expects an Eloquent model instance or class name');
        }

        return $instance->doGenerate($model, $config);
    }

    /**
     * Obtém a configuração do model ou usa padrões.
     */
    public function getConfigFromModel(Model $model): CodeConfig
    {
        if (method_exists($model, 'codeGenerateConfig')) {
            $configArray = $model->codeGenerateConfig();
            return CodeConfig::fromArray($configArray);
        }

        if (property_exists($model, 'codeGenerateConfig')) {
            $configArray = $model->codeGenerateConfig;
            return is_array($configArray)
                ? CodeConfig::fromArray($configArray)
                : CodeConfig::fromArray([]);
        }

        return CodeConfig::fromArray([
            'field' => $model->codeField ?? self::DEFAULT_FIELD,
            'length' => $model->codeLength ?? self::DEFAULT_LENGTH,
            'prefix' => $model->codePrefix ?? self::DEFAULT_PREFIX,
        ]);
    }

    /**
     * Executa a geração do código.
     *
     * @throws Exception
     */
    private function doGenerate(Model $model, CodeConfig $config): CodeGenerateResult
    {
        $table = $model->getTable();
        $connectionName = $model->getConnectionName();
        $field = $config->field;
        $effectivePrefix = $config->getEffectivePrefix();

        // Verifica se há mudança no prefixo (para reset de sequência)
        $lastPrefix = $this->getLastPrefix($table, $field);
        $isReset = $config->resetPattern !== null && $lastPrefix !== null && $lastPrefix !== $effectivePrefix;

        return DB::connection($connectionName)->transaction(function () use (
            $table,
            $connectionName,
            $config,
            $effectivePrefix,
            $isReset,
            $field
        ) {
            $driver = DatabaseDriverFactory::make($connectionName);
            $fieldInfo = $this->getFieldInfo($driver, $table, $field);

            // Validações
            $this->validatePrefixCompatibility($fieldInfo, $effectivePrefix, $field);
            $this->validateLength($config->length, $fieldInfo->length, $field);

            $prefixLength = strlen($effectivePrefix);
            $idLength = $config->length - $prefixLength;

            if ($idLength <= 0) {
                throw new Exception("Prefix '{$effectivePrefix}' is too long for length {$config->length}");
            }

            // Busca o último código
            $latest = DB::connection($connectionName)
                ->table($table)
                ->select($field)
                ->where($field, 'like', $effectivePrefix . '%')
                ->orderBy($field, 'desc')
                ->lockForUpdate()
                ->first();

            // Calcula o próximo ID
            if ($isReset || $latest === null || !isset($latest->{$field})) {
                $nextId = 1;
            } else {
                $maxFullId = (string) $latest->{$field};
                $currentPrefix = substr($maxFullId, 0, $prefixLength);

                // Se o prefixo mudou, reinicia
                if ($currentPrefix !== $effectivePrefix) {
                    $nextId = 1;
                } else {
                    $maxId = substr($maxFullId, $prefixLength, $idLength);
                    $nextId = (int) $maxId + 1;
                }
            }

            // Gera o código com verificação de colisão
            $code = $this->generateUniqueCode(
                $table,
                $connectionName,
                $field,
                $effectivePrefix,
                $idLength,
                $nextId,
                $config->unique
            );

            // Salva o prefixo atual para futura referência
            $this->saveLastPrefix($table, $field, $effectivePrefix);

            return CodeGenerateResult::success($code, $field);
        });
    }

    /**
     * Obtém informações do campo, com cache se habilitado.
     *
     * @throws Exception
     */
    private function getFieldInfo(
        $driver,
        string $table,
        string $field
    ): \RiseTechApps\CodeGenerate\DTO\FieldInfo {
        $cacheKey = "codegenerate_schema_{$table}_{$field}";

        if ($this->cache !== null) {
            return $this->cache->remember(
                $cacheKey,
                config('code-generate.cache_ttl', 3600),
                fn () => $driver->getFieldInfo($table, $field)
            );
        }

        return $driver->getFieldInfo($table, $field);
    }

    /**
     * Valida compatibilidade entre tipo do campo e prefixo.
     *
     * @throws Exception
     */
    private function validatePrefixCompatibility(
        \RiseTechApps\CodeGenerate\DTO\FieldInfo $fieldInfo,
        string $prefix,
        string $field
    ): void {
        if ($fieldInfo->isNumeric() && !is_numeric($prefix) && $prefix !== '') {
            throw new Exception(
                "Field '{$field}' type is '{$fieldInfo->type}' but prefix '{$prefix}' is not numeric"
            );
        }
    }

    /**
     * Valida se o comprimento gerado cabe no campo.
     *
     * @throws Exception
     */
    private function validateLength(int $configLength, int $fieldLength, string $field): void
    {
        if ($configLength > $fieldLength) {
            throw new Exception(
                "Generated code length ({$configLength}) exceeds field '{$field}' max length ({$fieldLength})"
            );
        }
    }

    /**
     * Gera um código único, verificando colisões.
     *
     * @throws Exception
     */
    private function generateUniqueCode(
        string $table,
        ?string $connectionName,
        string $field,
        string $prefix,
        int $idLength,
        int $startId,
        bool $checkUniqueness
    ): string {
        $maxId = $startId;
        $attempts = 0;

        while ($attempts < $this->maxCollisionAttempts) {
            $code = $prefix . str_pad($maxId, $idLength, '0', STR_PAD_LEFT);

            // Verifica colisão apenas se necessário
            if ($checkUniqueness) {
                $exists = DB::connection($connectionName)
                    ->table($table)
                    ->where($field, $code)
                    ->exists();

                if (!$exists) {
                    return $code;
                }

                // Colisão encontrada, incrementa e tenta novamente
                $maxId++;
                $attempts++;
            } else {
                return $code;
            }
        }

        throw new Exception(
            "Could not generate unique code after {$this->maxCollisionAttempts} attempts. " .
            "Consider increasing the code length or checking for data inconsistencies."
        );
    }

    /**
     * Obtém o último prefixo usado (para reset de sequência).
     */
    private function getLastPrefix(string $table, string $field): ?string
    {
        $key = "codegenerate_prefix_{$table}_{$field}";

        if ($this->cache !== null) {
            return $this->cache->get($key);
        }

        return null;
    }

    /**
     * Salva o último prefixo usado.
     */
    private function saveLastPrefix(string $table, string $field, string $prefix): void
    {
        if ($this->cache === null) {
            return;
        }

        $key = "codegenerate_prefix_{$table}_{$field}";
        $this->cache->put($key, $prefix, config('code-generate.cache_ttl', 3600));
    }

    /**
     * Limpa o cache de schema para uma tabela.
     */
    public function clearSchemaCache(string $table, string $field = 'code'): void
    {
        if ($this->cache === null) {
            return;
        }

        $this->cache->forget("codegenerate_schema_{$table}_{$field}");
        $this->cache->forget("codegenerate_prefix_{$table}_{$field}");
    }

    /**
     * Limpa todo o cache do package.
     */
    public function clearAllCache(): void
    {
        if ($this->cache === null) {
            return;
        }

        // Limpa todas as chaves que começam com 'codegenerate_'
        // Nota: isso varia de acordo com o driver de cache
        // Implementação básica para cache array/file
        // Para Redis/Memcached, pode precisar de implementação diferente
    }

    /**
     * Define o número máximo de tentativas para resolver colisões.
     */
    public function setMaxCollisionAttempts(int $attempts): self
    {
        $this->maxCollisionAttempts = $attempts;
        return $this;
    }
}
