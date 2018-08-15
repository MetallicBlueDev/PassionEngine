<?php

namespace TREngine\Engine\Base\PdoSpecific;

/**
 * Contenu spécifique à MySql.
 *
 * @author Sébastien Villemain
 */
class MysqlSpecific extends PdoPlatformSpecific
{

    public function &getColumnsListQuery(string $fullTableName): string
    {
        $sql = "SHOW FULL COLUMNS FROM '" . $fullTableName . "'";
        return $sql;
    }

    public function &getTablesListQuery(string $databasePrefix): string
    {
        $sql = "SHOW TABLES LIKE '" . $databasePrefix . "%'";
        return $sql;
    }
}