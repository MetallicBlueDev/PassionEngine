<?php

declare(strict_types = 1);

/* -----------------------------------------------------------------------
 * PassionEngine
 * The blue passionflower CMS Engine, written with love and passion.
 * ----------------------------------------------------------------------- */

use TREngine\Engine\Core\CoreSecure;
use TREngine\Engine\Core\CoreMain;
use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Exec\ExecTimeMarker;

// Marque le passage dans l'index
define("PASSION_ENGINE_BOOTSTRAP",
       true);

// Chargement et exécution de la sécurité
require __DIR__ . DIRECTORY_SEPARATOR . 'Engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

if (CoreSecure::debuggingMode()) {
    ExecTimeMarker::startMeasurement("all");
}

try {
    // Mesure principal utilisable en permanence
    ExecTimeMarker::startMeasurement("main");

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

if (CoreSecure::debuggingMode()) {
    ExecTimeMarker::stopMeasurement("all");
    CoreLogger::displayDebugInformations();
}