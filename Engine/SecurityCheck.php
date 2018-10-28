<?php

namespace TREngine\Engine;

use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreInfo;
use TREngine\Engine\Core\CoreSecure;

// Initialisation principal
if (!defined("PASSION_ENGINE_INITIALIZED")) {
    define("PASSION_ENGINE_INITIALIZED",
           true);

    // Vérification du serveur et de la version PHP
    if (!class_exists("CoreInfo",
                      false)) {
        require __DIR__ . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'CoreInfo.php';

        // Les Superglobales ne peuvent pas être appelées directement dans une classe.
        // Injection des informations dans un environnement sécurisé
        // http://php.net/manual/fr/language.variables.variable.php
        // http://php.net/manual/fr/language.variables.superglobals.php
        foreach ($GLOBALS as $gvName => $gvValue) {
            if (substr($gvName,
                       0,
                       1) === "_") {
                CoreInfo::addGlobalVars($gvName,
                                        $gvValue);
            }

            unset(${$gvName});
        }

        unset($GLOBALS);
        unset($gvName);
        unset($gvValue);

        CoreInfo::initialize();

        // Vérification de la compatibilité du moteur avec la version de PHP
        if (!CoreInfo::compatibleVersion()) {
            echo"<h1>Sorry, but the PHP version currently running is too old to understand PassionEngine.</h1>"
            . "<br />Please upgrade version, with passion."
            . "<br />Your PHP version: " . PASSION_ENGINE_PHP_VERSION
            . "<br />Minimum PHP version: " . PASSION_ENGINE_PHP_MINIMUM_VERSION;
            exit();
        }
    }

    // Vérification de l'inclusion du chargeur
    if (!class_exists("CoreLoader",
                      false)) {
        require __DIR__ . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'CoreLoader.php';
    }
}

// Initialisation du chargeur de classe
if (!defined("PASSION_ENGINE_AUTOLOADED")) {
    define("PASSION_ENGINE_AUTOLOADED",
           true);
    CoreLoader::affectRegister();
}

// Initialisation du système de sécurité
if (!defined("PASSION_ENGINE_SECURED")) {
    define("PASSION_ENGINE_SECURED",
           true);
    CoreSecure::checkInstance();
}
