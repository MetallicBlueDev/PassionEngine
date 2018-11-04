<?php

namespace PassionEngine\Engine\Core;

use ReflectionClass;
use PassionEngine\Engine\Fail\FailLoader;
use PassionEngine\Engine\Fail\FailBase;

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
    public const CLASS_FILE = 'Class';

    /**
     * Fichier de pilotage de base de données.
     *
     * @var string
     */
    public const BASE_FILE = 'Base';

    /**
     * Fichier de gestion du cache.
     *
     * @var string
     */
    public const CACHE_FILE = 'Cache';

    /**
     * Fichier du base du moteur.
     *
     * @var string
     */
    public const CORE_FILE = 'Core';

    /**
     * Fichier d'aide à la manipulation.
     *
     * @var string
     */
    public const EXEC_FILE = 'Exec';

    /**
     * Fichier d'erreur.
     *
     * @var string
     */
    public const FAIL_FILE = 'Fail';

    /**
     * Fichier de bibliothèque.
     *
     * @var string
     */
    public const LIBRARY_FILE = 'Lib';

    /**
     * Fichier représentant un block.
     *
     * @var string
     */
    public const BLOCK_FILE = 'Block';

    /**
     * Fichier représentant un module.
     *
     * @var string
     */
    public const MODULE_FILE = 'Module';

    /**
     * Fichier représentant une traduction.
     *
     * @var string
     */
    public const TRANSLATE_FILE = 'Translate';

    /**
     * Extension spécifique pour la traduction.
     *
     * @var string
     */
    public const TRANSLATE_EXTENSION = 'lang';

    /**
     * Fichier représentant une inclusion spécifique.
     *
     * @var string
     */
    public const INCLUDE_FILE = 'inc';

    /**
     * Fichier attaché au moteur.
     *
     * @var string
     */
    public const ENGINE_SUBTYPE = 'Engine';

    /**
     * Fichier détaché du moteur.
     *
     * @var string
     */
    public const CUSTOM_SUBTYPE = 'Custom';

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
     * Namespace à la racine.
     *
     * @var string
     */
    private const MAIN_NAMESPACE = 'PassionEngine\\';

    /**
     * Namespace de base.
     *
     * @var string
     */
    private const NAMESPACE_PATTERN = '{ROOT}{ORIGIN}\{TYPE}\{PREFIX}{KEYNAME}';

    /**
     * Tableau des classes chargées.
     *
     * @var array array('name' => 'path')
     */
    private static $loadedFiles = null;

    private function __construct()
    {

    }

    /**
     * Inscription du chargeur de classe.
     *
     * @throws FailLoader
     */
    public static function affectRegister(): void
    {
        if (!is_null(self::$loadedFiles)) {
            throw new FailLoader('loader already registered',
                                 FailBase::getErrorCodeName(4));
        }

        self::$loadedFiles = array();

        require __DIR__ . DIRECTORY_SEPARATOR . 'CoreLoaderInfo.php';
        $CoreLoaderClass = new ReflectionClass(new CoreLoader());

        if (!spl_autoload_register(array(
                $CoreLoaderClass->getName(),
                'classLoader'),
                                   true)) {
            throw new FailLoader('spl_autoload_register fail',
                                 FailBase::getErrorCodeName(4));
        }
    }

    /**
     * Chargeur de classe.
     *
     * @param string $fullClassName Nom complet de la classe.
     * @return bool Chargé.
     */
    public static function &classLoader(string $fullClassName): bool
    {
        $info = new CoreLoaderInfo($fullClassName);
        return self::manageLoad($info);
    }

    /**
     * Chargeur de fichier de traduction.
     * Permet de charger des fichiers qui sont spécifiques à la traduction.
     *
     * @param string $rootDirectoryPath Chemin racine contenant le dossier de traduction.
     * @return bool Chargé.
     */
    public static function &translateLoader(string $rootDirectoryPath): bool
    {
        $info = new CoreLoaderInfo($rootDirectoryPath);
        $info->fileType = self::TRANSLATE_FILE;
        return self::manageLoad($info);
    }

    /**
     * Chargeur de fichier à inclure.
     * Permet de charger des fichiers qui ne sont pas des classes.
     *
     * @param string $includeKeyName Clé spécifique pour inclure le fichier (exemple Includes_cache correspondant au chemin Includes/cache.inc.php).
     * @return bool Chargé.
     */
    public static function &includeLoader(string $includeKeyName): bool
    {
        $info = new CoreLoaderInfo($includeKeyName);
        $info->fileType = self::INCLUDE_FILE;
        return self::manageLoad($info);
    }

    /**
     * Vérifie la disponibilité de la classe et de sa méthode éventuellement.
     *
     * @param string $className Nom de la classe.
     * @param string $methodName Nom de la méthode.
     * @param bool $static Appel d'instance ou statique.
     * @return bool L'appel peut être effectué.
     */
    public static function &isCallable(string $className,
                                       string $methodName = '',
                                       bool $static = false): bool
    {
        $info = new CoreLoaderInfo($className);
        self::buildKeyNameAndFileType($info);
        $rslt = self::loaded($info);

        if ($rslt && !empty($methodName)) {
            // Défini le comportement de l'appel
            if ($static) {
                $rslt = is_callable("{$info->keyName}::{$methodName}");
            } else {
                $rslt = is_callable(array(
                    $info->keyName,
                    $methodName));
            }
        }
        return $rslt;
    }

    /**
     * Appel une méthode d'un objet ou une méthode statique d'une classe.
     *
     * @param string $callback
     * @param mixed $example func_get_args()
     * @return mixed
     */
    public static function &callback(string $callback)
    {
        $args = func_get_args();
        $count = count($args);
        $args = ($count > 1) ? array_splice($args,
                                            1,
                                            $count - 1) : array();

        // Appel de la méthode
        $rslt = call_user_func_array($callback,
                                     $args);

        if ($rslt === false) {
            CoreLogger::addDebug('Failed to execute callback \'' . $callback . '\'.');
        }
        return $rslt;
    }

    /**
     * Retourne le chemin absolu pour un fichier à inclure.
     * Permet de trouver des fichiers qui ne sont pas des classes.
     *
     * @param string $includeKeyName Nom de la clé correspondant au fichier demandé (exemple Includes_cache correspondant au chemin Includes/cache.inc.php).
     * @return string
     */
    public static function &getIncludeAbsolutePath(string $includeKeyName): string
    {
        $info = new CoreLoaderInfo($includeKeyName);
        $info->fileType = self::INCLUDE_FILE;
        return self::getAbsolutePath($info);
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
        $info = new CoreLoaderInfo($rootDirectoryPath);
        $info->fileType = self::TRANSLATE_FILE;
        return self::getAbsolutePath($info);
    }

    /**
     * Retourne le nom complet de la classe.
     *
     * @param string $className Nom court ou nom complet de la classe.
     * @param string $prefixName Préfixe de la classe si c'est un nom court qui nécessite plus de précision.
     * @return string
     */
    public static function &getFullQualifiedClassName(string $className,
                                                      string $prefixName = ''): string
    {
        $info = new CoreLoaderInfo($className);

        if (!empty($prefixName)) {
            $info->prefixName = $prefixName . '\\';
        }

        self::buildKeyNameAndFileType($info);
        return $info->keyName;
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
        $path = str_replace(self::MAIN_NAMESPACE,
                            '',
                            $fullClassName);

        // Conversion du namespace en dossier
        $path = str_replace('\\',
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
                                                     string $language = ''): string
    {
        $path = self::getFilePathFromNamespace($rootDirectoryPath) . DIRECTORY_SEPARATOR . self::TRANSLATE_FILE . DIRECTORY_SEPARATOR;

        if (!empty($language)) {
            $path .= $language;
        } else if (self::isCallable('CoreTranslate')) {
            $path .= CoreTranslate::getInstance()->getCurrentLanguage();
        }

        $path .= '.' . self::TRANSLATE_EXTENSION;
        return $path;
    }

    /**
     * Retourne le chemin vers le fichier contenant le fichier à inclure.
     *
     * @param string $includeKeyName Nom de la clé correspondant au fichier demandé (exemple Includes_cache correspondant au chemin Includes/cache.inc.php).
     * @return string
     */
    public static function &getFilePathFromInclude(string $includeKeyName): string
    {
        $path = str_replace('_',
                            DIRECTORY_SEPARATOR,
                            $includeKeyName) . '.' . self::INCLUDE_FILE;
        return $path;
    }

    /**
     * Retourne le chemin absolu.
     *
     * @param CoreLoaderInfo $info Information sur le fichier.
     * @return string
     */
    private static function &getAbsolutePath(CoreLoaderInfo &$info): string
    {
        self::buildKeyNameAndFileType($info);
        self::buildFilePath($info);
        return $info->path;
    }

    /**
     * Construction de la clé correspondant au fichier.
     *
     * @param CoreLoaderInfo $info Information sur le fichier.
     */
    private static function buildUniqueFileKey(CoreLoaderInfo &$info): void
    {
        if (empty($info->uniqueFileKey)) {
            $uniqueKey = $info->keyName;

            switch ($info->fileType) {
                case self::TRANSLATE_FILE:
                    $uniqueKey .= '.' . self::TRANSLATE_EXTENSION;
                    break;
                case self::INCLUDE_FILE:
                    $uniqueKey .= '.' . self::INCLUDE_FILE;
                    break;
            }

            $info->uniqueFileKey = $uniqueKey;
        }
    }

    /**
     * Vérifie si le fichier demandé a été chargé.
     *
     * @param CoreLoaderInfo $info Information sur le fichier.
     * @return bool Chargé.
     */
    private static function loaded(CoreLoaderInfo &$info): bool
    {
        self::buildUniqueFileKey($info);
        return isset(self::$loadedFiles[$info->uniqueFileKey]);
    }

    /**
     * Chargeur de fichier.
     *
     * @param CoreLoaderInfo $info Information sur le fichier.
     * @return bool Chargé.
     * @throws FailLoader
     */
    private static function &manageLoad(CoreLoaderInfo &$info): bool
    {
        $loaded = false;

        if (empty($info->keyName)) {
            throw new FailLoader('empty file name',
                                 FailBase::getErrorCodeName(4),
                                                            array($info->keyName, $info->fileType));
        }

        self::buildKeyNameAndFileType($info);
        $loaded = self::loaded($info);

        if (!$loaded) {
            $loaded = self::load($info);
        }
        return $loaded;
    }

    /**
     * Chargeur de fichier.
     *
     * @param CoreLoaderInfo $info Information sur le fichier.
     * @return bool
     * @throws FailLoader
     */
    private static function &load(CoreLoaderInfo &$info): bool
    {
        $loaded = false;
        self::buildFilePath($info);

        if (is_file($info->path)) {
            $loaded = self::loadFilePath($info);
        } else {
            switch ($info->fileType) {
                case self::BLOCK_FILE:
                    CoreLogger::addUserAlert(CoreTranslate::getConstantDescription(FailBase::getErrorCodeName(26)));
                    break;
                case self::MODULE_FILE:
                    CoreLogger::addUserAlert(CoreTranslate::getConstantDescription(FailBase::getErrorCodeName(23)));
                    break;
                case self::TRANSLATE_FILE:
                    // Aucune traduction disponible
                    break;
                default:
                    throw new FailLoader('unable to load file',
                                         FailBase::getErrorCodeName(4),
                                                                    array($info->keyName, $info->fileType));
            }
        }
        return $loaded;
    }

    /**
     * Construction du chemin et du type de fichier.
     *
     * @param CoreLoaderInfo $info Information sur le fichier.
     */
    private static function buildKeyNameAndFileType(CoreLoaderInfo &$info): void
    {
        if ($info->fileType === self::TRANSLATE_FILE) {
            self::buildGenericKeyNameAndFileTypeFromNamespace($info);
        } else if (strpos($info->keyName,
                          '\Block\Block') !== false) {
            $info->fileType = self::BLOCK_FILE;
        } else if (strpos($info->keyName,
                          'Module\Module') !== false) {
            $info->fileType = self::MODULE_FILE;
        } else if (strpos($info->keyName,
                          '\\') === false) {
            self::buildGenericKeyNameAndFileTypeFromNamespace($info);
        }

        if (empty($info->fileType)) {
            // Type par défaut
            $info->fileType = self::CLASS_FILE;
        }
    }

    /**
     * Construction du chemin et du type de fichier.
     *
     * @param CoreLoaderInfo $info Information sur le fichier.
     */
    private static function buildGenericKeyNameAndFileTypeFromNamespace(CoreLoaderInfo &$info): void
    {

        foreach (self::NAMESPACE_TYPES as $namespaceType) {
            if (strrpos($info->keyName,
                        $namespaceType,
                        -strlen($info->keyName)) !== false) {
                if (empty($info->fileType)) {
                    $info->fileType = $namespaceType;
                }

                $fullClassName = str_replace('{ROOT}',
                                             self::MAIN_NAMESPACE,
                                             self::NAMESPACE_PATTERN);

                $fullClassName = str_replace('{KEYNAME}',
                                             $info->keyName,
                                             $fullClassName);

                $fullClassName = str_replace('{PREFIX}',
                                             $info->prefixName,
                                             $fullClassName);
                $fullClassName = str_replace('{TYPE}',
                                             $namespaceType,
                                             $fullClassName);

                $namespaceOrigin = (strrpos($fullClassName,
                                            $namespaceType . self::CUSTOM_SUBTYPE) !== false) ? self::CUSTOM_SUBTYPE : self::ENGINE_SUBTYPE;
                $info->keyName = str_replace('{ORIGIN}',
                                             $namespaceOrigin,
                                             $fullClassName);
                break;
            }
        }
    }

    /**
     * Détermine le chemin vers le fichier.
     *
     * @param CoreLoaderInfo $info Information sur le fichier.
     * @throws FailLoader
     */
    private static function buildFilePath(CoreLoaderInfo &$info): void
    {
        if (empty($info->path)) {
            $path = '';

            switch ($info->fileType) {
                // No break
                case self::BASE_FILE:
                case self::CACHE_FILE:
                case self::CORE_FILE:
                case self::EXEC_FILE:
                case self::FAIL_FILE:
                case self::LIBRARY_FILE :
                case self::CLASS_FILE:
                case self::BLOCK_FILE:
                case self::MODULE_FILE:
                    $path = self::getFilePathFromNamespace($info->keyName);
                    break;
                case self::TRANSLATE_FILE:
                    $path = self::getFilePathFromTranslate($info->keyName);
                    break;
                case self::INCLUDE_FILE:
                    $path = self::getFilePathFromInclude($info->keyName);
                    break;
                default:
                    throw new FailLoader('can not determine the file path',
                                         FailBase::getErrorCodeName(4),
                                                                    array($info->keyName, $info->fileType));
            }

            $info->path = PASSION_ENGINE_ROOT_DIRECTORY . DIRECTORY_SEPARATOR . $path . '.php';
        }
    }

    /**
     * Charge le fichier suivant son type, son nom et son chemin.
     *
     * @param CoreLoaderInfo $info Information sur le fichier.
     * @return bool
     */
    private static function &loadFilePath(CoreLoaderInfo &$info): bool
    {
        $loaded = false;

        switch ($info->fileType) {
            case self::TRANSLATE_FILE:
                ${self::TRANSLATE_EXTENSION} = array();
                break;
            case self::INCLUDE_FILE:
                ${self::INCLUDE_FILE} = array();
                break;
        }

        require $info->path;
        self::buildUniqueFileKey($info);
        self::$loadedFiles[$info->uniqueFileKey] = $info->path;
        $loaded = true;

        switch ($info->fileType) {
            case self::TRANSLATE_FILE:
                if (!empty(${self::TRANSLATE_EXTENSION}) && is_array(${self::TRANSLATE_EXTENSION})) {
                    CoreTranslate::getInstance()->affectCache(${self::TRANSLATE_EXTENSION});
                }
                break;
            case self::INCLUDE_FILE:
                CoreMain::getInstance()->getConfigs()->addInclude($info->keyName,
                                                                  ${self::INCLUDE_FILE});
                break;
        }
        return $loaded;
    }
}