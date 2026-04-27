<?php

namespace RiseTechApps\CodeGenerate\Contracts\Driver;

use RiseTechApps\CodeGenerate\DTO\FieldInfo;

interface DatabaseDriverInterface
{
    public function getFieldInfo(string $table, string $field): FieldInfo;
}
