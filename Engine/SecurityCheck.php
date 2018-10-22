<?php

namespace TREngine\Engine;

use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreInfo;
use TREngine\Engine\Core\CoreSecure;

// Initialisation principal
if (!defined("TR_ENGINE_INITIALIZED")) {
    define("TR_ENGINE_INITIALIZED",
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
            echo"<h1>Sorry, but the PHP version currently running is too old to understand TR ENGINE.</h1>"
            . "<br />Please, seriously consider updating your system."
            . "<br />Your PHP version: " . TR_ENGINE_PHP_VERSION
            . "<br />Minimum PHP version: " . TR_ENGINE_PHP_MINIMUM_VERSION;
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
if (!defined("TR_ENGINE_AUTOLOADED")) {
    define("TR_ENGINE_AUTOLOADED",
           true);
    CoreLoader::affectRegister();
}

// Initialisation du système de sécurité
if (!defined("TR_ENGINE_SECURE")) {
    define("TR_ENGINE_SECURE",
           true);
    CoreSecure::checkInstance();
}
