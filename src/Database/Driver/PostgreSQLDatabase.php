<?php

namespace RiseTechApps\CodeGenerate\Database\Driver;

use Exception;
use Illuminate\Support\Facades\DB;
use RiseTechApps\CodeGenerate\Contracts\Driver\DatabaseDriverInterface;
use RiseTechApps\CodeGenerate\DTO\FieldInfo;

readonly class PostgreSQLDatabase implements DatabaseDriverInterface
{
    public function __construct(private ?string $connection = null)
    {
    }

    /**
     * @throws Exception
     */
    public function getFieldInfo(string $table, string $field): FieldInfo
    {
        $connection = DB::connection($this->connection);
        $database = $connection->getDatabaseName();

        $sql = 'SELECT column_name AS "column_name", data_type AS "data_type",
                       character_maximum_length AS "column_length",
                       numeric_precision AS "numeric_precision"
                FROM information_schema.columns
                WHERE table_catalog = :database AND table_name = :table';

        $rows = $connection->select($sql, [
            'database' => $database,
            'table' => $table
        ]);

        foreach ($rows as $col) {
            if ($field === $col->column_name) {
                $fieldLength = 255;

                if (!empty($col->column_length)) {
                    $fieldLength = (int) $col->column_length;
                } elseif (!empty($col->numeric_precision)) {
                    $fieldLength = (int) $col->numeric_precision;
                }

                return new FieldInfo(
                    type: $col->data_type,
                    length: $fieldLength
                );
            }
        }

        throw new Exception("Field '{$field}' not found in table '{$table}'");
    }
}
