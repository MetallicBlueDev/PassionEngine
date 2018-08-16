<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Fail\FailLoader;
use TREngine\Engine\Fail\FailBase;

/**
 * Gestionnaire de chargeur de classe.
 *
 * @author Sébastien Villemain
 */
class CoreLoader
{

    /**
     * Fichier représentant une classe PHP pure.
     *
     * @var string
     */
    public const CLASS_FILE = "Class";

    /**
     * Fichier de pilotage de base de données.
     *
     * @var string
     */
    public const BASE_FILE = "Base";

    /**
     * Fichier de gestion du cache.
     *
     * @var string
     */
    public const CACHE_FILE = "Cache";

    /**
     * Fichier du base du moteur.
     *
     * @var string
     */
    public const CORE_FILE = "Core";

    /**
     * Fichier d'aide à la manipulation.
     *
     * @var string
     */
    public const EXEC_FILE = "Exec";

    /**
     * Fichier d'erreur.
     *
     * @var string
     */
    public const FAIL_FILE = "Fail";

    /**
     * Fichier de bibliothèque.
     *
     * @var string
     */
    public const LIBRARY_FILE = "Lib";

    /**
     * Fichier représentant un block.
     *
     * @var string
     */
    public const BLOCK_FILE = "Block";

    /**
     * Fichier représentant un module.
     *
     * @var string
     */
    public const MODULE_FILE = "Module";

    /**
     * Fichier représentant une traduction.
     *
     * @var string
     */
    public const TRANSLATE_FILE = "Translate";

    /**
     * Extension spécifique pour la traduction.
     *
     * @var string
     */
    public const TRANSLATE_EXTENSION = "lang";

    /**
     * Fichier représentant une inclusion spécifique.
     *
     * @var string
     */
    public const INCLUDE_FILE = "inc";

    /**
     * Fichier attaché au moteur.
     *
     * @var string
     */
    public const ENGINE_SUBTYPE = "Engine";

    /**
     * Fichier détaché du moteur.
     *
     * @var string
     */
    public const CUSTOM_SUBTYPE = "Custom";

    /**
     * Les types de namespace possible.
     */
    private const NAMESPACE_TYPES = array(
        self::BASE_FILE,
        self::CACHE_FILE,
        self::CORE_FILE,
        self::EXEC_FILE,
        self::FAIL_FILE,
        self::LIBRARY_FILE,
        self::BLOCK_FILE,
        self::MODULE_FILE
    );

    /**
     * Namespace de base.
     *
     * @var string
     */
    private const NAMESPACE_PATTERN = "TREngine\{ORIGIN}\{TYPE}\{PREFIX}{KEYNAME}";

    /**
     * Tableau des classes chargées.
     *
     * @var array array("name" => "path")
     */
    private static $loadedFiles = null;

    /**
     * Inscription du chargeur de classe.
     *
     * @throws FailLoader
     */
    public static function affectRegister(): void
    {
        if (!is_null(self::$loadedFiles)) {
            throw new FailLoader("loader already registered",
                                 FailBase::getErrorCodeName(4));
        }

        self::$loadedFiles = array();

        if (!spl_autoload_register(array(
                    'TREngine\Engine\Core\CoreLoader',
                    'classLoader'),
                                   true)) {
            throw new FailLoader("spl_autoload_register fail",
                                 FailBase::getErrorCodeName(4));
        }
    }

    /**
     * Chargeur de classe.
     *
     * @param string $fullClassName Nom complet de la classe.
     * @return bool true chargé.
     */
    public static function &classLoader(string $fullClassName): bool
    {
        // Type indéterminé
        return self::manageLoad($fullClassName,
                                "");
    }

    /**
     * Chargeur de fichier de traduction.
     * Permet de charger des fichiers qui sont spécifiques à la traduction.
     *
     * @param string $rootDirectoryPath Chemin racine contenant le dossier de traduction.
     * @return bool true chargé.
     */
    public static function &translateLoader(string $rootDirectoryPath): bool
    {
        return self::manageLoad($rootDirectoryPath,
                                self::TRANSLATE_FILE);
    }

    /**
     * Chargeur de fichier "à inclure".
     * Permet de charger des fichiers qui ne sont pas des classes.
     *
     * @param string $includeKeyName Clé spécifique pour inclure le fichier (exemple configs_cache correspondant au chemin configs/cache.inc.php).
     * @return bool true chargé.
     */
    public static function &includeLoader(string $includeKeyName): bool
    {
        return self::manageLoad($includeKeyName,
                                self::INCLUDE_FILE);
    }

    /**
     * Vérifie la disponibilité de la classe et de ca methode éventuellement.
     *
     * @param string $className Nom de la classe.
     * @param string $methodName Nom de la méthode.
     * @param bool $static Appel d'instance ou statique.
     * @return bool true l'appel peut être effectué.
     */
    public static function &isCallable(string $className,
                                       string $methodName = "",
                                       bool $static = false): bool
    {
        $rslt = false;
        $fileType = "";

        if (!empty($methodName)) {
            self::buildKeyNameAndFileType($className,
                                          $fileType);

            // Vérifie si la classe est en mémoire
            if (self::isLoaded($className,
                               $fileType)) {
                // Pour les pages plus complexes comme le module management
                $pos = strpos($className,
                              ".");

                if ($pos !== false) { // exemple : Module_Management_Security.setting
                    $className = substr($className,
                                        0,
                                        $pos);
                }

                // Défini le comportement de l'appel
                if ($static) {
                    $rslt = is_callable("{$className}::{$methodName}");
                } else {
                    $rslt = is_callable(array(
                        $className,
                        $methodName));
                }
            }
        } else {
            self::buildKeyNameAndFileType($className,
                                          $fileType);
            $rslt = self::isLoaded($className,
                                   $fileType);
        }
        return $rslt;
    }

    /**
     * Appel une methode d'un objet ou une méthode statique d'une classe.
     *
     * @param string $callback
     * @param mixed $example func_get_args()
     * @return mixed
     */
    public static function &callback(string $callback)
    {
        $fileType = "";
        self::buildKeyNameAndFileType($callback,
                                      $fileType);

        $args = func_get_args();
        $count = count($args);
        $args = ($count > 1) ? array_splice($args,
                                            1,
                                            $count - 1) : array();

        // Appel de la méthode
        $rslt = call_user_func_array($callback,
                                     $args);

        if ($rslt === false) {
            CoreLogger::addException("Failed to execute callback '" . $callback . "'. FileType (extension): " . $fileType);
        }
        return $rslt;
    }

    /**
     * Retourne le chemin absolu pour un fichier "à inclure".
     * Permet de trouver des fichiers qui ne sont pas des classes.
     *
     * @param string $includeKeyName Nom de la clé correspondant au fichier demandé (exemple configs_cache correspondant au chemin configs/cache.inc.php)..
     * @return string
     */
    public static function &getIncludeAbsolutePath(string $includeKeyName): string
    {
        return self::getAbsolutePath($includeKeyName,
                                     self::INCLUDE_FILE);
    }

    /**
     * Retourne le chemin absolu d'un fichier de traduction.
     * Permet de trouver des fichiers qui sont spécifiques à la traduction.
     *
     * @param string $rootDirectoryPath Chemin racine contenant le dossier de traduction.
     * @return string chemin absolu ou nulle.
     */
    public static function &getTranslateAbsolutePath(string $rootDirectoryPath): string
    {
        return self::getAbsolutePath($rootDirectoryPath,
                                     self::TRANSLATE_FILE);
    }

    /**
     * Retourne le nom complet de la classe.
     *
     * @param string $className Nom court ou nom complet de la classe.
     * @param string $prefixName Préfixe de la classe si c'est un nom court qui nécessite plus de précision.
     * @return string
     */
    public static function &getFullQualifiedClassName(string $className,
                                                      string $prefixName = ""): string
    {
        $fileType = "";

        if (!empty($prefixName)) {
            $prefixName .= "\\";
        }

        self::buildKeyNameAndFileType($className,
                                      $fileType,
                                      $prefixName);
        return $className;
    }

    /**
     * Retourne le chemin vers le fichier contenant la classe.
     *
     * @param string $fullClassName Nom complet de la classe.
     * @return string
     */
    public static function &getFilePathFromNamespace(string $fullClassName): string
    {
        // Supprime le premier namespace
        $path = str_replace("TREngine\\",
                            "",
                            $fullClassName);

        // Conversion du namespace en dossier
        $path = str_replace("\\",
                            DIRECTORY_SEPARATOR,
                            $path);
        return $path;
    }

    /**
     * Retourne le chemin vers le fichier contenant la traduction.
     *
     * @param string $rootDirectoryPath Chemin racine contenant le dossier de traduction.
     * @param string $language Langue contenue dans le fichier.
     * @return string
     */
    public static function &getFilePathFromTranslate(string $rootDirectoryPath,
                                                     string $language = ""): string
    {
        $path = $rootDirectoryPath . DIRECTORY_SEPARATOR . self::TRANSLATE_FILE . DIRECTORY_SEPARATOR;

        if (!empty($language)) {
            $path .= $language;
        } else if (self::isCallable("CoreTranslate")) {
            $path .= CoreTranslate::getInstance()->getCurrentLanguage();
        }

        $path .= "." . self::TRANSLATE_EXTENSION;
        return $path;
    }

    /**
     * Retourne le chemin absolu.
     *
     * @param string $keyName Fichier demandé.
     * @param string $fileType
     * @return string chemin absolu ou nulle.
     */
    private static function &getAbsolutePath(string $keyName,
                                             string $fileType): string
    {
        self::buildKeyNameAndFileType($keyName,
                                      $fileType);
        return self::getFilePath($keyName,
                                 $fileType);
    }

    /**
     * Retourne la clé correspondant au fichier.
     *
     * @param string $keyName
     * @param string $fileType
     * @return string
     */
    private static function &getLoadedFileKey(string $keyName,
                                              string $fileType): string
    {
        $loadKeyName = $keyName;

        switch ($fileType) {
            case self::TRANSLATE_FILE:
                $loadKeyName .= "." . self::TRANSLATE_EXTENSION;
                break;
        }

        return $loadKeyName;
    }

    /**
     * Vérifie si le fichier demandé a été chargé.
     *
     * @param string $keyName Fichier demandé.
     * @param string $fileType Type de fichier.
     * @return bool true si c'est déjà chargé.
     */
    private static function isLoaded(string $keyName,
                                     string $fileType): bool
    {
        return isset(self::$loadedFiles[self::getLoadedFileKey($keyName,
                                                               $fileType)]);
    }

    /**
     * Chargeur de fichier (uniquement si besoin).
     *
     * @param string $keyName Nom de la classe ou du fichier.
     * @param string $fileType Type de fichier.
     * @return bool true chargé.
     * @throws FailLoader
     */
    private static function &manageLoad(string &$keyName,
                                        string $fileType): bool
    {
        $loaded = false;

        if (empty($keyName)) {
            throw new FailLoader("empty file name",
                                 FailBase::getErrorCodeName(4),
                                                            array($keyName, $fileType));
        }

        self::buildKeyNameAndFileType($keyName,
                                      $fileType);
        $loaded = self::isLoaded($keyName,
                                 $fileType);

        // Si ce n'est pas déjà chargé
        if (!$loaded) {
            $loaded = self::load($keyName,
                                 $fileType);
        }
        return $loaded;
    }

    /**
     * Chargeur de classe.
     *
     * @param string $keyName
     * @param string $fileType
     * @return bool
     * @throws FailLoader
     */
    private static function &load(string $keyName,
                                  string $fileType): bool
    {
        $loaded = false;
        $path = self::getFilePath($keyName,
                                  $fileType);

        if (is_file($path)) {
            $loaded = self::loadFilePath($keyName,
                                         $fileType,
                                         $path);
        } else {
            switch ($fileType) {
                case self::BLOCK_FILE:
                    CoreLogger::addError(FailBase::getErrorCodeDescription(FailBase::getErrorCodeName(26)));
                    break;
                case self::MODULE_FILE:
                    CoreLogger::addError(FailBase::getErrorCodeDescription(FailBase::getErrorCodeName(23)));
                    break;
                case self::TRANSLATE_FILE:
                    // Aucune traduction disponible
                    break;
                default:
                    throw new FailLoader("unable to load file",
                                         FailBase::getErrorCodeName(4),
                                                                    array($keyName, $fileType));
            }
        }
        return $loaded;
    }

    /**
     * Construction du chemin et du type de fichier.
     *
     * @param string $fullClassName
     * @param string $fileType
     * @param string $prefixName
     */
    private static function buildKeyNameAndFileType(string &$fullClassName,
                                                    string &$fileType,
                                                    string $prefixName = ""): void
    {
        if (empty($fileType)) {
            self::buildGenericKeyNameAndFileType($fullClassName,
                                                 $fileType,
                                                 $prefixName);
        }

        if ($fileType === self::TRANSLATE_FILE) {
            $fakeFileType = "";
            self::buildGenericKeyNameAndFileType($fullClassName,
                                                 $fakeFileType,
                                                 $prefixName);

            if ($fakeFileType !== null && $fakeFileType !== $fileType) {
                $fullClassName = self::getFilePathFromNamespace($fullClassName);
            }
        }
    }

    /**
     * Construction du chemin et du type de fichier.
     *
     * @param string $keyName
     * @param string $fileType
     * @param string $prefixName
     */
    private static function buildGenericKeyNameAndFileType(string &$keyName,
                                                           string &$fileType,
                                                           string $prefixName): void
    {
        if (strpos($keyName,
                   "\Block\Block") !== false) {
            $fileType = self::BLOCK_FILE;
        } else if (strpos($keyName,
                          "\Module\Module") !== false) {
            $fileType = self::MODULE_FILE;
        } else if (strpos($keyName,
                          "\\") === false) {
            self::buildGenericKeyNameAndFileTypeFromNamespace($keyName,
                                                              $fileType,
                                                              $prefixName);
        }

        if (empty($fileType)) {
            // Type par défaut
            $fileType = self::CLASS_FILE;
        }
    }

    /**
     * Construction du chemin et du type de fichier.
     *
     * @param string $keyName
     * @param string $fileType
     * @param string $prefixName
     */
    private static function buildGenericKeyNameAndFileTypeFromNamespace(string &$keyName,
                                                                        string &$fileType,
                                                                        string $prefixName): void
    {

        foreach (self::NAMESPACE_TYPES as $namespaceType) {
            if (strrpos($keyName,
                        $namespaceType,
                        -strlen($keyName)) !== false) {
                $fileType = $namespaceType;

                $keyName = str_replace("{KEYNAME}",
                                       $keyName,
                                       self::NAMESPACE_PATTERN);
                $keyName = str_replace("{PREFIX}",
                                       $prefixName,
                                       $keyName);
                $keyName = str_replace("{TYPE}",
                                       $namespaceType,
                                       $keyName);

                $namespaceOrigin = (strrpos($keyName,
                                            $namespaceType . self::CUSTOM_SUBTYPE) !== false) ? self::CUSTOM_SUBTYPE : self::ENGINE_SUBTYPE;
                $keyName = str_replace("{ORIGIN}",
                                       $namespaceOrigin,
                                       $keyName);
                break;
            }
        }
    }

    /**
     * Détermine le chemin vers le fichier.
     *
     * @param string $fullClassName
     * @param string $fileType
     * @return string
     * @throws FailLoader
     */
    private static function &getFilePath(string $fullClassName,
                                         string $fileType): string
    {
        $path = "";

        switch ($fileType) {
            case self::BASE_FILE:
            case self::CACHE_FILE:
            case self::CORE_FILE:
            case self::EXEC_FILE:
            case self::FAIL_FILE:
            case self::LIBRARY_FILE :
            case self::CLASS_FILE:
            case self::BLOCK_FILE:
            case self::MODULE_FILE:
                $path = self::getFilePathFromNamespace($fullClassName);
                break;
            case self::TRANSLATE_FILE:
                $path = self::getFilePathFromTranslate($fullClassName);
                break;
            case self::INCLUDE_FILE:
                $path = str_replace("_",
                                    DIRECTORY_SEPARATOR,
                                    $fullClassName) . "." . $fileType;
                break;
            default:
                throw new FailLoader("can not determine the file path",
                                     FailBase::getErrorCodeName(4),
                                                                array($fullClassName, $fileType));
        }

        $path = TR_ENGINE_INDEX_DIRECTORY . DIRECTORY_SEPARATOR . $path . ".php";
        return $path;
    }

    /**
     * Charge le fichier suivant son type, son nom et son chemin.
     *
     * @param string $keyName
     * @param string $fileType
     * @param string $path
     * @return bool
     */
    private static function &loadFilePath(string $keyName,
                                          string $fileType,
                                          string $path): bool
    {
        $loaded = false;

        switch ($fileType) {
            case self::TRANSLATE_FILE:
                ${self::TRANSLATE_EXTENSION} = array();
                break;
            case self::INCLUDE_FILE:
                ${self::INCLUDE_FILE} = array();
                break;
        }

        require $path;
        self::$loadedFiles[self::getLoadedFileKey($keyName,
                                                  $fileType)] = $path;
        $loaded = true;

        switch ($fileType) {
            case self::TRANSLATE_FILE:
                if (!empty(${self::TRANSLATE_EXTENSION}) && is_array(${self::TRANSLATE_EXTENSION})) {
                    CoreTranslate::getInstance()->affectCache(${self::TRANSLATE_EXTENSION});
                }
                break;
            case self::INCLUDE_FILE:
                CoreMain::getInstance()->getConfigs()->addInclude($keyName,
                                                                  ${self::INCLUDE_FILE});
                break;
        }
        return $loaded;
    }
}