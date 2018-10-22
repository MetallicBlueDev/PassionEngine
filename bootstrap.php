<?php

declare(strict_types = 1);

// -----------------------------------------------------------------------
// -- TR ENGINE = PUISSANCE + SIMPLICITE + EVOLUTIVITE
// -- Puissance par une quantité d'énergie fournie au meilleur de PHP
// -- Simplicité par une interface intuitive
// -- Evolutivité par une grande souplesse d'adaptation du moteur
// -----------------------------------------------------------------------

use TREngine\Engine\Core\CoreSecure;
use TREngine\Engine\Core\CoreMain;
use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Exec\ExecTimeMarker;

// Marque le passage dans l'index
define("TR_ENGINE_BOOTSTRAP",
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