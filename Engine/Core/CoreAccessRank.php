<?php

namespace TREngine\Engine\Core;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Référence les rangs possibles utilisés par le moteur.
 *
 * @author Sébastien Villemain
 */
class CoreAccessRank {

    /**
     * Accès non défini (cas de désactivation).
     *
     * @var int
     */
    const NONE = 0;

    /**
     * Accès publique.
     *
     * @var int
     */
    const PUBLIC = 1;

    /**
     * Accès aux membres.
     *
     * @var int
     */
    const REGISTRED = 2;

    /**
     * Accès aux administrateurs.
     *
     * @var int
     */
    const ADMIN = 3;

    /**
     * Accès avec droit spécifique.
     *
     * @var int
     */
    const SPECIFIC_RIGHT = 4;

    /**
     * Liste des rangs valides.
     *
     * @var array array("name" => 0)
     */
    const RANK_LIST = array(
        "ACCESS_NONE" => self::NONE,
        "ACCESS_PUBLIC" => self::PUBLIC,
        "ACCESS_REGISTRED" => self::REGISTRED,
        "ACCESS_ADMIN" => self::ADMIN,
        "ACCESS_SPECIFIC_RIGHT" => self::SPECIFIC_RIGHT);

}