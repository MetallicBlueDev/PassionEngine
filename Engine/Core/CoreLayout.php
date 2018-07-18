<?php

namespace TREngine\Engine\Core;

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
    public const DEFAULT = "default";

    /**
     * Affichage uniquement du module (si javascript activé).
     * layout=module
     *
     * @var string
     */
    public const MODULE = "module";

    /**
     * Affichage uniquement du module (mode forcé).
     * layout=modulepage
     *
     * @var string
     */
    public const MODULE_PAGE = "modulepage";

    /**
     * Affichage uniquement du block (si javascript activé).
     * layout=block
     *
     * @var string
     */
    public const BLOCK = "block";

    /**
     * Affichage uniquement du block (mode forcé).
     * layout=blockpage
     *
     * @var string
     */
    public const BLOCK_PAGE = "blockpage";

    /**
     * Requête permettant d'obtenir la mise en plage.
     * layout=?
     *
     * @var string
     */
    public const REQUEST_LAYOUT = "layout";

    /**
     * Requête permettant d'obtenir le nom du module.
     * module=?
     *
     * @var string
     */
    public const REQUEST_MODULE = "module";

    /**
     * Requête permettant d'obtenir le type de block.
     * blockType=?
     *
     * @var string
     */
    public const REQUEST_BLOCKTYPE = "blockType";

    /**
     * Requête permettant d'obtenir l'identifiant du block.
     * blockId=?
     *
     * @var string
     */
    public const REQUEST_BLOCKID = "blockId";

    /**
     * Requête permettant d'obtenir le nom du viewer.
     * view=?
     *
     * @var string
     */
    public const REQUEST_VIEW = "view";

    /**
     * Requête permettant d'obtenir le nom de la page.
     * page=?
     *
     * @var string
     */
    public const REQUEST_PAGE = "page";

}