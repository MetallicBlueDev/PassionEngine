<?php

namespace TREngine\Engine\Fail;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Référence les erreurs possibles.
 *
 * @author Sébastien Villemain
 */
class FailFrom {

    /**
     * Une erreur générée par une couche basse du moteur.
     */
    public const ENGINE = -1;

    /**
     * Une erreur générée la couche SQL du moteur.
     */
    public const SQL = 10;

    /**
     * Une erreur générée le chargeur de classe.
     */
    public const LOADER = 20;

    /**
     * Une erreur générée la couche du cache du moteur.
     *
     * @var int
     */
    public const CACHE = 30;

    /**
     * Une erreur générée la partie block.
     *
     * @var int
     */
    public const BLOCK = 40;

    /**
     * Une erreur générée la partie module.
     *
     * @var int
     */
    public const MODULE = 40;

    /**
     * Une erreur générée la partie template.
     *
     * @var int
     */
    public const MAKESTYLE = 50;

    /**
     * Liste des codes d'erreur.
     *
     * @var array
     */
    public const ERROR_CODES = array(
        0 => "genericErrorCode",
        1 => "accessRank",
        2 => "cacheType",
        3 => "cacheCode",
        4 => "loader",
        5 => "cachePath",
        6 => "sqlPath",
        7 => "configPath",
        8 => "siteClosed",
        9 => "requestHash",
        10 => "badUrl",
        11 => "badQueryString",
        12 => "badRequestReferer",
        13 => "sqlType",
        14 => "sqlCode",
        15 => "blockType",
        16 => "blockSide",
        17 => "makeStyle",
        18 => "makeStyleConfig",
        19 => "sqlReq",
        20 => "netConnect",
        21 => "netSelect",
        22 => "netCanUse",
        9999 => "??"
    );

}