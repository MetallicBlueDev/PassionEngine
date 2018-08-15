<?php

namespace TREngine\Engine\Base\PdoSpecific;

/**
 * Contenu spécifique à Microsoft SQL Server / SQL Azure.
 *
 * @author Sébastien Villemain
 */
class SqlsrvSpecific extends PgsqlSpecific
{

    public function &getTablesListQuery(string $databasePrefix): string
    {
        $sql = "SELECT name FROM sysobjects WHERE type = 'U' AND name LIKE '" . $databasePrefix . "%' ORDER BY name";
        return $sql;
    }
}