<?php

namespace RiseTechApps\CodeGenerate;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class CodeGenerateServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->offerPublishing();
        $this->registerBlueprintMacros();
    }

    /**
     * Register the application services.
     */
    #[\Override]
    public function register(): void
    {
        $this->mergeConfig();

        $this->app->singleton(CodeGenerate::class, fn() => new CodeGenerate());

        $this->app->alias(CodeGenerate::class, 'code-generate');
    }

    /**
     * Registra os macros do Blueprint.
     */
    protected function registerBlueprintMacros(): void
    {
        // Macro codeGenerate para coluna única
        if (!Blueprint::hasMacro('codeGenerate')) {
            Blueprint::macro('codeGenerate', function (
                ?string $column = null,
                ?int $length = null,
                bool $unique = true,
                string $type = 'string'
            ) {
                /** @var Blueprint $this */
                $columnName = $column ?? CodeGenerate::DEFAULT_FIELD;
                $columnLength = $length ?? CodeGenerate::DEFAULT_LENGTH;

                $definition = match ($type) {
                    'string', 'varchar' => $this->string($columnName, $columnLength),
                    'char' => $this->char($columnName, $columnLength),
                    'integer', 'int' => $this->integer($columnName),
                    'bigint' => $this->bigInteger($columnName),
                    'smallint' => $this->smallInteger($columnName),
                    default => throw new \InvalidArgumentException("Unsupported column type: {$type}"),
                };

                if ($unique) {
                    $this->unique($columnName);
                }

                return $definition;
            });
        }

        // Macro codeGenerates para múltiplas colunas
        if (!Blueprint::hasMacro('codeGenerates')) {
            Blueprint::macro('codeGenerates', function (array $columns) {
                /** @var Blueprint $this */
                $definitions = [];

                foreach ($columns as $config) {
                    $column = $config['column'] ?? CodeGenerate::DEFAULT_FIELD;
                    $length = $config['length'] ?? CodeGenerate::DEFAULT_LENGTH;
                    $unique = $config['unique'] ?? true;
                    $type = $config['type'] ?? 'string';

                    $definitions[] = $this->codeGenerate($column, $length, $unique, $type);
                }

                return $definitions;
            });
        }
    }

    /**
     * Mescla a configuração do package.
     */
    protected function mergeConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/config.php',
            'code-generate'
        );
    }

    /**
     * Oferece publicação de arquivos.
     */
    protected function offerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('code-generate.php'),
            ], 'code-generate-config');
        }
    }
}
