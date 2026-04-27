<?php

namespace RiseTechApps\CodeGenerate\DTO;

readonly class CodeGenerateResult
{
    public function __construct(
        public string $code,
        public string $field,
        public bool $success = true,
        public ?string $message = null
    ) {}

    public static function success(string $code, string $field): self
    {
        return new self(code: $code, field: $field, success: true);
    }

    public static function error(string $field, string $message): self
    {
        return new self(code: '', field: $field, success: false, message: $message);
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'field' => $this->field,
            'success' => $this->success,
            'message' => $this->message,
        ];
    }
}
