<?php

namespace PassionEngine\Engine\Base\PdoSpecific;

/**
 * Contenu spécifique à PostgreSQL.
 *
 * @author Sébastien Villemain
 */
class PgsqlSpecific extends PdoPlatformSpecific
{

    public function &getColumnsListQuery(string $fullTableName): string
    {
        $sql = 'SELECT column_name FROM information_schema.columns WHERE LOWER(table_name) = \'' . strtolower($fullTableName) . '\'';
        return $sql;
    }

    public function &getTablesListQuery(string $databasePrefix): string
    {
        $sql = 'SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\' AND table_name LIKE \'' . $databasePrefix . '%\'';
        return $sql;
    }
}