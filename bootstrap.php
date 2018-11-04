<?php

declare(strict_types = 1);

/* -----------------------------------------------------------------------
 * PassionEngine
 * The blue passionflower CMS Engine, written with love and passion.
 * ----------------------------------------------------------------------- */

use PassionEngine\Engine\Core\CoreSecure;
use PassionEngine\Engine\Core\CoreMain;
use PassionEngine\Engine\Core\CoreLogger;
use PassionEngine\Engine\Exec\ExecTimeMarker;

/**
 * Détermine si le mode debug est activé.
 *
 * @link https://github.com/MetallicBlueDev/PassionEngine/wiki/Debugging-mode
 * @var bool
 */
define('PASSION_ENGINE_DEBUGMODE',
       (bool) file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'Includes' . DIRECTORY_SEPARATOR . 'debugmode'));

/**
 * Marque le passage dans l'index.
 *
 * @var bool
 */
define('PASSION_ENGINE_BOOTSTRAP',
       true);

// Chargement et exécution de la sécurité
require __DIR__ . DIRECTORY_SEPARATOR . 'Engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

if (PASSION_ENGINE_DEBUGMODE) {
    ExecTimeMarker::startMeasurement('all');
}

try {
    // Mesure principal utilisable en permanence
    ExecTimeMarker::startMeasurement('main');

    // Préparation du moteur
    CoreMain::checkInstance();

    // Recherche de nouveaux composants
    if (CoreMain::getInstance()->newComponentDetected()) {
        // Installation des nouveaux composants
        CoreMain::getInstance()->install();
    } else {
        // Démarrage classique
        CoreMain::getInstance()->start();
    }
} catch (\Throwable $ex) {
    CoreSecure::getInstance()->catchException($ex);
}

