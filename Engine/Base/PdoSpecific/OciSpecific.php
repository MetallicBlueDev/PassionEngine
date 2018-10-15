<?php

namespace TREngine\Engine\Base\PdoSpecific;

/**
 * Contenu spécifique à Oracle Call Interface.
 *
 * @author Sébastien Villemain
 */
class OciSpecific extends PdoPlatformSpecific
{

    public function &getColumnsListQuery(string $fullTableName): string
    {
        $sql = 'SELECT COLUMN_NAME FROM ALL_TAB_COLUMNS	WHERE LOWER(TABLE_NAME) = \'' . strtoupper($fullTableName) . '\'';
        return $sql;
    }

    public function &getTablesListQuery(string $databasePrefix): string
    {
        $sql = 'SELECT TABLE_NAME FROM ALL_TABLES WHERE TABLE_NAME LIKE \'' . $databasePrefix . '%\'';
        return $sql;
    }
}