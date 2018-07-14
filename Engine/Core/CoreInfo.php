<?php

namespace TREngine\Engine\Core;

/**
 * Recherche d'information rapide sur le moteur d'exécution et son environnement coté serveur.
 *
 * Attention, il faut faire en sorte que cette classe soit compatible PHP 5.6 (date minimale 08/2014).
 *
 * http://php.net/supported-versions.php
 * 5.6 : 31 Dec 2018 -> INCOMPATIBLE
 * 7.0 : 3 Dec 2018 -> INCOMPATIBLE
 * 7.1 : 1 Dec 2019 <- Version minimale
 * 7.2 : 30 Nov 2020 <- Compatible (vérifié via doc mais jamais exécuté)
 * 7.3 : ???
 *
 * @author Sébastien Villemain
 */
class CoreInfo
{

    /**
     * Indique si la classe a été initialisée.
     *
     * @var bool
     */
    private static $initialized = false;

    /**
     * Tableau temporaire contenant les pointeurs des variables mémorisées.
     *
     * @var array
     */
    private static $unsafeGlobalVars = array();

    private function __construct()
    {

    }

    /**
     * Mémorise les variables.
     * Utilisé pour mémoriser les contenus des Superglobales.
     *
     * Les Superglobales ne peuvent pas être appelées directement dans une classe.
     * http://php.net/manual/fr/language.variables.variable.php
     * http://php.net/manual/fr/language.variables.superglobals.php
     *
     * @param string $name Pointeur vers le nom de la variable.
     * @param array $value Pointeur vers le contenu de la variable.
     */
    public static function addGlobalVars(&$name,
                                         &$value)
    {
        if (!self::$initialized) {
            self::$unsafeGlobalVars[$name] = $value;
        } else {
            exit("Invalid access.");
        }
    }

    /**
     * Retourne le tableau contenant les pointeurs des variables précédemment mémorisées.
     * Attention, données brutes, non sécurisées.
     *
     * @return array
     */
    public static function &getGlobalVars($name)
    {
        return self::$unsafeGlobalVars[$name];
    }

    /**
     * Détermine si la version PHP actuelle est compatible.
     *
     * @return bool
     */
    public static function compatibleVersion()
    {
        return (TR_ENGINE_PHP_VERSION >= TR_ENGINE_PHP_MINIMUM_VERSION);
    }

    /**
     * Création des constantes contenant les informations sur l'environnement.
     */
    public static function initialize()
    {
        if (!self::$initialized) {
            self::$initialized = true;

            $info = new CoreInfo();

            /**
             * Version php sous forme x.x.x.x (exemple : 7.1.0).
             */
            define("TR_ENGINE_PHP_MINIMUM_VERSION",
                   "7.1.0");

            /**
             * Version php sous forme x.x.x.x (exemple : 5.2.9.2).
             */
            define("TR_ENGINE_PHP_VERSION",
                   $info->getPhpVersion());

            /**
             * Chemin jusqu'à la racine.
             *
             * @var string
             */
            define("TR_ENGINE_INDEX_DIRECTORY",
                   $info->getIndexDirectory());

            /**
             * Adresse URL complète jusqu'à TR ENGINE.
             *
             * @var string
             */
            define("TR_ENGINE_URL",
                   $info->getUrlAddress());

            /**
             * Le système d'exploitation qui exécute TR ENGINE.
             *
             * @var string
             */
            define("TR_ENGINE_PHP_OS",
                   $info->getPhpOs());

            /**
             * Le retour de chariot du serveur TR ENGINE.
             *
             * @var string
             */
            define("TR_ENGINE_CRLF",
                   $info->getCrLf());

            /**
             * Numéro de version du moteur.
             *
             * Controle de révision
             * xx -> version courante
             * xx -> fonctionnalitées ajoutées
             * xx -> bugs ou failles critiques corrigés
             * xx -> bug mineur
             *
             * @var string
             */
            define("TR_ENGINE_VERSION",
                   "0.6.2.0");
        }
    }

    /**
     * Retourne la version du PHP.
     *
     * @return string
     */
    private function getPhpVersion()
    {
        return preg_replace("/[^0-9.]/",
                            "",
                            (preg_replace("/(_|-|[+])/",
                                          ".",
                                          phpversion())));
    }

    /**
     * Retourne le chemin jusqu'à la racine.
     *
     * @return string
     */
    private function getIndexDirectory()
    {
        $baseDir = "";

        // Recherche du chemin absolu depuis n'importe quel fichier
        if (defined("TR_ENGINE_INDEX")) {
            // Nous sommes dans l'index
            $baseDir = getcwd();
        } else {
            $baseDir = $this->getIndexDirectoryFromCustomFolder();
        }
        return $baseDir;
    }

    /**
     * Retourne le chemin jusqu'à la racine depuis le dossier actuel.
     *
     * @return string
     */
    private function getIndexDirectoryFromCustomFolder()
    {
        $pathFromWorkingFolder = "";

        // Chemin de base
        $baseName = str_replace(self::getGlobalServer("SCRIPT_NAME"),
                                                      "",
                                                      self::getGlobalServer("SCRIPT_FILENAME"));

        if (!empty($baseName)) {
            $baseName = str_replace("/",
                                    DIRECTORY_SEPARATOR,
                                    $baseName);
            $workingDirectory = getcwd();

            if (!empty($workingDirectory)) {
                $pathFromWorkingFolder = $this->getPathFromWorkingFolder($baseName,
                                                                         $workingDirectory);
            }
        }

        // Verification du résultat
        $baseDir = "";

        if (!empty($pathFromWorkingFolder) && is_file($baseName . DIRECTORY_SEPARATOR . $pathFromWorkingFolder . DIRECTORY_SEPARATOR . "configs" . DIRECTORY_SEPARATOR . "config.inc.php")) {
            $baseDir = $baseName . DIRECTORY_SEPARATOR . $pathFromWorkingFolder;
        } else if (is_file($baseName . DIRECTORY_SEPARATOR . "configs" . DIRECTORY_SEPARATOR . "config.inc.php")) {
            $baseDir = $baseName;
        } else {
            $baseDir = $baseName;
        }
        return $baseDir;
    }

    /**
     * Retourne le chemin relatif jusqu'à la racine depuis le dossier actuel.
     *
     * @param string $baseName
     * @param string $workingDirectory
     * @return string
     */
    private function getPathFromWorkingFolder($baseName,
                                              $workingDirectory)
    {
        $path = "";

        // Nous isolons le chemin en plus jusqu'au fichier
        $path = str_replace($baseName,
                            "",
                            $workingDirectory);

        if (!empty($path)) {
            // Suppression du slash supplémentaire
            if ($path[0] === DIRECTORY_SEPARATOR) {
                $path = substr($path,
                               1);
            }

            // Vérification en se repérant sur l'emplacement du fichier de configuration
            while (!is_file($baseName . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . "configs" . DIRECTORY_SEPARATOR . "config.inc.php")) {
                // Nous remontons d'un cran
                $path = dirname($path);

                // La recherche n'aboutira pas
                if ($path === ".") {
                    break;
                }
            }
        }
        return $path;
    }

    /**
     * Retourne l'adresse URL complète jusqu'à TR ENGINE.
     *
     * @return string
     */
    private function getUrlAddress()
    {
        $rslt = "";

        // Recherche de l'URL courante
        $requestUri = self::getGlobalServer("REQUEST_URI");

        if (!empty($requestUri)) {
            $currentUrlArray = $this->getUrlAddressExploded($requestUri);

            // Recherche du dossier courant
            $urlBaseArray = explode(DIRECTORY_SEPARATOR,
                                    TR_ENGINE_INDEX_DIRECTORY);

            // Construction du lien
            $urlFinal = $this->getUrlAddressBuilded($currentUrlArray,
                                                    $urlBaseArray);
            $serverName = self::getGlobalServer("SERVER_NAME");
            $rslt = ((empty($urlFinal)) ? $serverName : $serverName . "/" . $urlFinal);
        }
        return $rslt;
    }

    /**
     * Retourne un tableau contenant les fragments de l'URL.
     *
     * @param string $requestUri
     * @return array
     */
    private function getUrlAddressExploded($requestUri)
    {
        if (substr($requestUri,
                   -1) === "/") {
            $requestUri = substr($requestUri,
                                 0,
                                 -1);
        }

        if ($requestUri[0] === "/") {
            $requestUri = substr($requestUri,
                                 1);
        }

        $requestUri = explode("/",
                              $requestUri);
        return $requestUri;
    }

    /**
     * Retourne l'adresse URL reconstruite.
     *
     * @param array $currentUrlArray
     * @param array $urlBaseArray
     * @return string
     */
    private function getUrlAddressBuilded($currentUrlArray,
                                          $urlBaseArray)
    {
        $urlFinal = "";
        $currentUrlCounter = count($currentUrlArray);
        $urlBaseCounter = count($urlBaseArray);

        for ($i = $currentUrlCounter - 1; $i >= 0; $i--) {
            for ($j = $urlBaseCounter - 1; $j >= 0; $j--) {
                if (empty($urlBaseArray[$j])) {
                    continue;
                }

                if ($currentUrlArray[$i] !== $urlBaseArray[$j]) {
                    break;
                }

                if (empty($urlFinal)) {
                    $urlFinal = $currentUrlArray[$i];
                } else {
                    $urlFinal = $currentUrlArray[$i] . "/" . $urlFinal;
                }

                $urlBaseArray[$j] = "";
            }
        }
        return $urlFinal;
    }

    /**
     * Retourne la plateforme sur lequel est PHP.
     *
     * @return string
     */
    private function getPhpOs()
    {
        return strtoupper(substr(PHP_OS,
                                 0,
                                 3));
    }

    /**
     * Retourne le retour chariot du serveur.
     *
     * @return string
     */
    private function getCrLf()
    {
        $rslt = "";

        // Le retour chariot de chaque OS
        if (TR_ENGINE_PHP_OS === "WIN") {
            $rslt = "\r\n";
        } else if (TR_ENGINE_PHP_OS === "MAC") {
            $rslt = "\r";
        } else {
            $rslt = "\n";
        }
        return $rslt;
    }

    /**
     * Classe autorisée à manipuler $_SERVER (lecture seule).
     *
     * @param string $keyName
     * @return string
     */
    private static function getGlobalServer($keyName)
    {
        return (isset(self::$unsafeGlobalVars['_SERVER'])) ? self::$unsafeGlobalVars['_SERVER'][$keyName] : "";
    }
}