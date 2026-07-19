<?php

namespace RiseTechApps\CodeGenerate\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use RiseTechApps\CodeGenerate\CodeGenerate;
use RiseTechApps\CodeGenerate\DTO\CodeConfig;

trait HasCodeGenerate
{
    protected static bool $ignoreCodeGenerateUpdating = false;

    public static function bootHasCodeGenerate(): void
    {
        static::creating(function (Model $model) {
            $connection = $model->getConnectionName();
            $schemaBuilder = Schema::connection($connection ?? config('database.default'));

            if (!$schemaBuilder->hasTable($model->getTable())) {
                return;
            }

            $configs = static::getCodeGenerateConfigs($model);

            foreach ($configs as $config) {
                if ($model->{$config->field} === null) {
                    try {
                        $result = CodeGenerate::generate($model, $config);
                        if ($result->success) {
                            $model->{$config->field} = $result->code;
                        }
                    } catch (Exception $e) {
                        // Log do erro mas não interrompe a criação
                        if (config('code-generate.throw_on_error', true)) {
                            throw $e;
                        }
                        logger()->warning('CodeGenerate failed: ' . $e->getMessage());
                    }
                }
            }
        });

        static::updating(function (Model $model) {
            if (!self::$ignoreCodeGenerateUpdating) {
                $configs = static::getCodeGenerateConfigs($model);

                foreach ($configs as $config) {
                    $model->{$config->field} = $model->getOriginal($config->field);
                }
            }
        });
    }

    /**
     * Obtém todas as configurações de geração de código para o model.
     *
     * @return array<CodeConfig>
     */
    protected static function getCodeGenerateConfigs(Model $model): array
    {
        // Suporte a múltiplas configurações
        if (method_exists($model, 'codeGenerateConfigs')) {
            $configArrays = $model->codeGenerateConfigs();
            return array_map(CodeConfig::fromArray(...), $configArrays);
        }

        // Configuração única via método
        if (method_exists($model, 'codeGenerateConfig')) {
            return [CodeConfig::fromArray($model->codeGenerateConfig())];
        }

        // Configuração única via propriedade
        if (property_exists($model, 'codeGenerateConfig')) {
            $configArray = $model->codeGenerateConfig;
            if (is_array($configArray)) {
                // Verifica se é uma lista de configurações ou uma única configuração
                if (isset($configArray[0]) && is_array($configArray[0])) {
                    return array_map(CodeConfig::fromArray(...), $configArray);
                }
                return [CodeConfig::fromArray($configArray)];
            }
        }

        // Propriedades legadas
        return [CodeConfig::fromArray([
            'field' => $model->codeField ?? 'code',
            'length' => $model->codeLength ?? CodeGenerate::DEFAULT_LENGTH,
            'prefix' => $model->codePrefix ?? CodeGenerate::DEFAULT_PREFIX,
        ])];
    }

    /**
     * Ativa/desativa a proteção de código em updates.
     */
    public static function ignoreCodeGenerateUpdating(bool $ignore = true): void
    {
        self::$ignoreCodeGenerateUpdating = $ignore;
    }

    /**
     * Permite atualizar o código manualmente.
     */
    public function updateCode(string $field = 'code', ?string $newCode = null): void
    {
        $configs = static::getCodeGenerateConfigs($this);

        foreach ($configs as $config) {
            if ($config->field === $field) {
                self::$ignoreCodeGenerateUpdating = true;
                $this->update([$field => $newCode ?? CodeGenerate::generate($this, $config)->code]);
                self::$ignoreCodeGenerateUpdating = false;
                return;
            }
        }

        throw new Exception("Field '{$field}' not found in code generate configuration");
    }

    /**
     * Regenera todos os códigos do model.
     *
     * @throws Exception
     */
    public function regenerateCodes(): void
    {
        $configs = static::getCodeGenerateConfigs($this);
        $updates = [];

        self::$ignoreCodeGenerateUpdating = true;

        foreach ($configs as $config) {
            $result = CodeGenerate::generate($this, $config);
            if ($result->success) {
                $updates[$config->field] = $result->code;
            }
        }

        if (!empty($updates)) {
            $this->update($updates);
        }

        self::$ignoreCodeGenerateUpdating = false;
    }

    /**
     * Obtém a configuração para um campo específico.
     */
    public function getCodeConfig(string $field = 'code'): ?CodeConfig
    {
        $configs = static::getCodeGenerateConfigs($this);

        foreach ($configs as $config) {
            if ($config->field === $field) {
                return $config;
            }
        }

        return null;
    }
}
