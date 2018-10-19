<?php

namespace TREngine\Engine\Core;

/**
 * Référence le nom des zones d'accès utilisées par le moteur.
 *
 * @author Sébastien Villemain
 */
class CoreAccessZone
{

    /**
     * Zone pour un module.
     *
     * @var string
     */
    public const MODULE = 'MODULE';

    /**
     * Zone pour un block.
     *
     * @var string
     */
    public const BLOCK = 'BLOCK';

    /**
     * Zone pour un client/une session.
     *
     * @var string
     */
    public const SESSION = 'SESSION';

    /**
     * Zone pour un élément de menu.
     *
     * @var string
     */
    public const MENU = 'MENU';

}