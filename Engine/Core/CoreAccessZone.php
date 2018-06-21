<?php

namespace TREngine\Engine\Core;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Référence le nom des zones d'accès utilisées par le moteur.
 *
 * @author Sébastien Villemain
 */
class CoreAccessZone {

    /**
     * Zone pour un module.
     *
     * @var string
     */
    public const MODULE = "MODULE";

    /**
     * Zone pour un block.
     *
     * @var string
     */
    public const BLOCK = "BLOCK";

}