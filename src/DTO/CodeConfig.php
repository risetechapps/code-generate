<?php

namespace RiseTechApps\CodeGenerate\DTO;

readonly class CodeConfig
{
    public function __construct(
        public string $field = 'code',
        public int $length = 4,
        public string $prefix = '',
        public ?string $resetPattern = null,
        public bool $unique = true,
        public string $type = 'string'
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            field: $config['field'] ?? 'code',
            length: $config['length'] ?? 4,
            prefix: $config['prefix'] ?? '',
            resetPattern: $config['resetPattern'] ?? null,
            unique: $config['unique'] ?? true,
            type: $config['type'] ?? 'string'
        );
    }

    public function getEffectivePrefix(): string
    {
        if ($this->resetPattern === null) {
            return $this->prefix;
        }

        return match ($this->resetPattern) {
            'Y' => $this->prefix . date('Y'),
            'y' => $this->prefix . date('y'),
            'M' => $this->prefix . date('Y-m'),
            'm' => $this->prefix . date('Ym'),
            'D' => $this->prefix . date('Y-m-d'),
            'd' => $this->prefix . date('Ymd'),
            default => $this->prefix . date($this->resetPattern),
        };
    }

    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'length' => $this->length,
            'prefix' => $this->prefix,
            'resetPattern' => $this->resetPattern,
            'unique' => $this->unique,
            'type' => $this->type,
        ];
    }
}
