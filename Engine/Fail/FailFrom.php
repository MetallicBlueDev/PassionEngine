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
    const ENGINE = -1;

    /**
     * Une erreur générée la couche SQL du moteur.
     */
    const SQL = 10;

    /**
     * Une erreur générée le chargeur de classe.
     */
    const LOADER = 20;

    /**
     * Une erreur générée la couche du cache du moteur.
     */
    const CACHE = 30;

}