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
     */
    public const CACHE = 30;

}