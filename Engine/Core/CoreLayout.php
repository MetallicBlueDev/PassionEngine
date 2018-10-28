<?php

namespace PassionEngine\Engine\Core;

/**
 * Référence le nom des dispositions utilisées par le moteur.
 * Symbolise le mode de mise en page.
 *
 * @author Sébastien Villemain
 */
class CoreLayout
{

    /**
     * Affichage par défaut.
     * layout=default
     *
     * @var string
     */
    public const DEFAULT_LAYOUT = 'default';

    /**
     * Nom de la page par défaut.
     * page=index
     *
     * @var string
     */
    public const DEFAULT_PAGE = 'index';

    /**
     * Nom de la méthode d'affichage principal.
     *
     * @var string
     */
    public const DEFAULT_VIEW_DISPLAY = 'display';

    /**
     * Nom de la méthode d'installation.
     *
     * @var string
     */
    public const DEFAULT_VIEW_INSTALL = 'install';

    /**
     * Nom de la méthode de désinstallation.
     *
     * @var string
     */
    public const DEFAULT_VIEW_UNINSTALL = 'uninstall';

    /**
     * Nom de la méthode de configuration.
     *
     * @var string
     */
    public const DEFAULT_VIEW_SETTING = 'setting';

    /**
     * Affichage uniquement du module (si javaScript activé).
     * layout=module
     *
     * @var string
     */
    public const MODULE = 'module';

    /**
     * Affichage uniquement du module (mode forcé).
     * layout=modulepage
     *
     * @var string
     */
    public const MODULE_PAGE = 'modulepage';

    /**
     * Affichage uniquement du block (si javaScript activé).
     * layout=block
     *
     * @var string
     */
    public const BLOCK = 'block';

    /**
     * Affichage uniquement du block (mode forcé).
     * layout=blockpage
     *
     * @var string
     */
    public const BLOCK_PAGE = 'blockpage';

    /**
     * Requête permettant d'obtenir la mise en plage.
     * layout=?
     *
     * @var string
     */
    public const REQUEST_LAYOUT = 'layout';

    /**
     * Requête permettant d'obtenir le nom du module.
     * module=?
     *
     * @var string
     */
    public const REQUEST_MODULE = 'module';

    /**
     * Requête permettant d'obtenir le type de block.
     * blockType=?
     *
     * @var string
     */
    public const REQUEST_BLOCKTYPE = 'blockType';

    /**
     * Requête permettant d'obtenir l'identifiant du block.
     * blockId=?
     *
     * @var string
     */
    public const REQUEST_BLOCKID = 'blockId';

    /**
     * Requête permettant d'obtenir la méthode d'affichage.
     * view=?
     *
     * @var string
     */
    public const REQUEST_VIEW = 'view';

    /**
     * Requête permettant d'obtenir le nom de la page.
     * page=?
     *
     * @var string
     */
    public const REQUEST_PAGE = 'page';

    /**
     * Block désactivé (position inconnue).
     *
     * @var int
     */
    public const BLOCK_SIDE_NONE = 0;

    /**
     * Block positionné le plus à droite.
     *
     * @var int
     */
    public const BLOCK_SIDE_RIGHT = 1;

    /**
     * Block positionné le plus à gauche.
     *
     * @var int
     */
    public const BLOCK_SIDE_LEFT = 2;

    /**
     * Block positionné le plus en haut.
     *
     * @var int
     */
    public const BLOCK_SIDE_TOP = 3;

    /**
     * Block positionné le plus en bas.
     *
     * @var int
     */
    public const BLOCK_SIDE_BOTTOM = 4;

    /**
     * Block positionné dans le module à droite.
     *
     * @var int
     */
    public const BLOCK_SIDE_MODULE_RIGHT = 5;

    /**
     * Block positionné dans le module à gauche.
     *
     * @var int
     */
    public const BLOCK_SIDE_MODULE_LEFT = 6;

    /**
     * Block positionné dans le module en haut.
     *
     * @var int
     */
    public const BLOCK_SIDE_MODULE_TOP = 7;

    /**
     * Block positionné dans le module en bas.
     *
     * @var int
     */
    public const BLOCK_SIDE_MODULE_BOTTOM = 8;

    /**
     * Liste des positions valides pour un block.
     *
     * @var array array('name' => 0)
     */
    public const BLOCK_SIDE_LIST = array(
        'right' => self::BLOCK_SIDE_RIGHT,
        'left' => self::BLOCK_SIDE_LEFT,
        'top' => self::BLOCK_SIDE_TOP,
        'bottom' => self::BLOCK_SIDE_BOTTOM,
        'moduleright' => self::BLOCK_SIDE_MODULE_RIGHT,
        'moduleleft' => self::BLOCK_SIDE_MODULE_LEFT,
        'moduletop' => self::BLOCK_SIDE_MODULE_TOP,
        'modulebottom' => self::BLOCK_SIDE_MODULE_BOTTOM
    );

}