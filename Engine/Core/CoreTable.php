<?php

namespace TREngine\Engine\Core;

/**
 * Référence le nom des tables en base de données utilisées par le moteur.
 *
 * @author Sébastien Villemain
 */
class CoreTable
{

    /**
     * Table des bannissements.
     *
     * @var string
     */
    public const BANNED = "banned";

    /**
     * Table des blocks.
     *
     * @var string
     */
    public const BLOCKS = "blocks";

    /**
     * Table d'affectation de la visibilité des blocks.
     *
     * @var string
     */
    public const BLOCKS_VISIBILITY = "blocks_visibility";

    /**
     * Table de configuration des blocks.
     *
     * @var string
     */
    public const BLOCKS_CONFIGS = "blocks_configs";

    /**
     * Table de la configuration du moteur.
     *
     * @var string
     */
    public const CONFIG = "configs";

    /**
     * Table des menus.
     *
     * @var string
     */
    public const MENUS = "menus";

    /**
     * Table de configuration des menus.
     *
     * @var string
     */
    public const MENUS_CONFIGS = "menus_configs";

    /**
     * Table des modules.
     *
     * @var string
     */
    public const MODULES = "modules";

    /**
     * Table de configuration des modules.
     *
     * @var string
     */
    public const MODULES_CONFIGS = "modules_configs";

    /**
     * Table des utilisateurs.
     *
     * @var string
     */
    public const USERS = "users";

    /**
     * Table des droits des utilisateurs.
     *
     * @var string
     */
    public const USERS_RIGHTS = "users_rights";

}