<?php

namespace RiseTechApps\CodeGenerate\Database\Driver;

use Exception;
use Illuminate\Support\Facades\DB;
use RiseTechApps\CodeGenerate\Contracts\Driver\DatabaseDriverInterface;
use RiseTechApps\CodeGenerate\DTO\FieldInfo;

readonly class MysqlDatabase implements DatabaseDriverInterface
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

        $sql = 'SELECT column_name AS "column_name", data_type AS "data_type", column_type AS "column_type"
                FROM information_schema.columns
                WHERE table_schema = :database AND table_name = :table';

        $rows = $connection->select($sql, [
            'database' => $database,
            'table' => $table
        ]);

        foreach ($rows as $col) {
            if ($field === $col->column_name) {
                $fieldLength = 255;

                preg_match("/(?<=\().+?(?=\)/", (string) $col->column_type, $matches);
                if (count($matches)) {
                    $fieldLength = (int) $matches[0];
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
