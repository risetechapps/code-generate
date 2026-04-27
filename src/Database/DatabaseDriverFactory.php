<?php

namespace RiseTechApps\CodeGenerate\Database;

use Exception;
use Illuminate\Support\Facades\DB;
use RiseTechApps\CodeGenerate\Contracts\Driver\DatabaseDriverInterface;
use RiseTechApps\CodeGenerate\Database\Driver\MysqlDatabase;
use RiseTechApps\CodeGenerate\Database\Driver\PostgreSQLDatabase;
use RiseTechApps\CodeGenerate\Database\Driver\SQLServerDatabase;

class DatabaseDriverFactory
{
    /**
     * @throws Exception
     */
    public static function make(?string $connectionName = null): DatabaseDriverInterface
    {
        $driver = DB::connection($connectionName)->getDriverName();

        return match ($driver) {
            'mysql' => new MysqlDatabase($connectionName),
            'pgsql' => new PostgreSQLDatabase($connectionName),
            'sqlsrv' => new SQLServerDatabase($connectionName),
            default => throw new Exception("Unsupported database driver: {$driver}"),
        };
    }
}
