<?php

namespace TREngine\Engine\Base\PdoSpecific;

/**
 * Modèle de base pour un contenu spécifique à une configuration de base de données.
 *
 * @author Sébastien Villemain
 */
class PdoPlatformSpecific
{

    /**
     * Retourne la commande à executer pour obtenir la liste des tables.
     *
     * @return string
     */
    public function &getTablesListQuery(string $databasePrefix): string
    {
        unset($databasePrefix);
    }

    /**
     * Retourne la commande à executer pour obtenir la liste des colonnes.
     *
     * @param string $fullTableName
     * @return string
     */
    public function &getColumnsListQuery(string $fullTableName): string
    {
        unset($fullTableName);
    }
}