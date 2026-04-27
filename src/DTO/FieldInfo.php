<?php

namespace RiseTechApps\CodeGenerate\DTO;

readonly class FieldInfo
{
    public function __construct(
        public string $type,
        public int $length
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            length: (int) $data['length']
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'length' => $this->length,
        ];
    }

    public function isNumeric(): bool
    {
        return in_array($this->type, ['int', 'integer', 'bigint', 'numeric', 'smallint'], true);
    }

    public function isString(): bool
    {
        return in_array($this->type, ['varchar', 'char', 'string', 'text'], true);
    }
}
