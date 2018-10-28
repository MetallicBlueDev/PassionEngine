<?php

namespace PassionEngine\Engine\Core;

/**
 * Référence les rangs possibles utilisés par le moteur.
 *
 * @author Sébastien Villemain
 */
class CoreAccessRank
{

    /**
     * Accès non défini (désactivé).
     *
     * @var int
     */
    public const NONE = 0;

    /**
     * Accès publique.
     *
     * @var int
     */
    public const PUBLIC = 1;

    /**
     * Accès aux membres.
     *
     * @var int
     */
    public const REGISTRED = 2;

    /**
     * Accès aux administrateurs.
     *
     * @var int
     */
    public const ADMIN = 3;

    /**
     * Accès avec droit spécifique.
     *
     * @var int
     */
    public const SPECIFIC_RIGHT = 4;

    /**
     * Liste des rangs valides.
     *
     * @var array array('name' => 0)
     */
    public const RANK_LIST = array(
        'ACCESS_NONE' => self::NONE,
        'ACCESS_PUBLIC' => self::PUBLIC,
        'ACCESS_REGISTRED' => self::REGISTRED,
        'ACCESS_ADMIN' => self::ADMIN,
        'ACCESS_SPECIFIC_RIGHT' => self::SPECIFIC_RIGHT);

}