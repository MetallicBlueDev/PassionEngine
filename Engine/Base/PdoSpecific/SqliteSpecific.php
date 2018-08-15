<?php

namespace TREngine\Engine\Base\PdoSpecific;

/**
 * Contenu spécifique à SQLite.
 *
 * @author Sébastien Villemain
 */
class SqliteSpecific extends PdoPlatformSpecific
{

    public function &getColumnsListQuery(string $fullTableName): string
    {
        $sql = "PRAGMA TABLE_INFO(" . $fullTableName . ")";
        return $sql;
    }

    public function &getTablesListQuery(string $databasePrefix): string
    {
        $sql = "SELECT NAME FROM SQLITE_MASTER WHERE TYPE = 'table' AND NAME LIKE '" . $databasePrefix . "%'";
        return $sql;
    }
}