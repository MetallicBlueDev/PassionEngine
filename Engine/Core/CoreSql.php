<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Base\BaseModel;
use TREngine\Engine\Fail\FailBase;
use TREngine\Engine\Fail\FailSql;

/**
 * Gestionnaire de la communication SQL.
 *
 * @author Sébastien Villemain
 */
class CoreSql
{

    /**
     * Gestionnaire SQL.
     *
     * @var CoreSql
     */
    private static $coreSql = null;

    /**
     * Modèle de base sélectionné.
     *
     * @var BaseModel
     */
    private $selectedBase = null;

    /**
     * Nouveau gestionnaire.
     *
     * @throws FailSql
     */
    private function __construct()
    {
        $databaseConfig = CoreMain::getInstance()->getConfigs()->getConfigDatabase();
        $fullClassName = $this->getBaseFullClassName($databaseConfig);
        $this->selectedBase = $this->makeSelectedBase($fullClassName,
                                                      $databaseConfig);
    }

    /**
     * Destruction du gestionnaire.
     */
    public function __destruct()
    {
        unset($this->selectedBase);
    }

    /**
     * Retourne l'instance du gestionnaire SQL.
     *
     * @return CoreSql
     */
    public static function &getInstance(): CoreSql
    {
        self::checkInstance();
        return self::$coreSql;
    }

    /**
     * Vérification de l'instance du gestionnaire SQL.
     */
    public static function checkInstance(): void
    {
        if (self::$coreSql === null) {
            self::$coreSql = new CoreSql();
        }
    }

    /**
     * Détermine si une connexion est disponible.
     *
     * @return bool
     */
    public static function hasConnection(): bool
    {
        return self::$coreSql !== null && self::$coreSql->selectedBase !== null;
    }

    /**
     * Retourne la liste des types de base supporté.
     *
     * @return array
     */
    public static function &getBaseList(): array
    {
        return CoreCache::getInstance()->getFileList(CoreLoader::ENGINE_SUBTYPE . DIRECTORY_SEPARATOR . CoreLoader::BASE_FILE,
                                                     CoreLoader::BASE_FILE);
    }

    /**
     * Retourne l'instance du pilote de base de données.
     *
     * @return BaseModel
     */
    public function &getSelectedBase(): BaseModel
    {
        return $this->selectedBase;
    }

    /**
     * Retourne le nom complet de la classe représentant le pilote de base de données.
     *
     * @param array $databaseConfig
     * @return string
     * @throws FailSql
     */
    private function getBaseFullClassName(array $databaseConfig): string
    {
        $fullClassName = "";
        $loaded = false;

        if (!empty($databaseConfig) && isset($databaseConfig['type'])) {
            $fullClassName = CoreLoader::getFullQualifiedClassName(CoreLoader::BASE_FILE . ucfirst($databaseConfig['type']));
            $loaded = CoreLoader::classLoader($fullClassName);
        }

        if (!$loaded) {
            throw new FailSql("database driver not found",
                              FailBase::getErrorCodeName(13),
                                                         array($databaseConfig['type']));
        }

        if (!CoreLoader::isCallable($fullClassName,
                                    "initialize")) {
            throw new FailSql("unable to initialize database",
                              FailBase::getErrorCodeName(14),
                                                         array($fullClassName));
        }
        return $fullClassName;
    }

    /**
     * Création de l'instance du pilote de la base de données.
     *
     * @param string $fullClassName
     * @param array $databaseConfig
     * @return BaseModel
     * @throws FailSql
     */
    private function makeSelectedBase(string $fullClassName,
                                      array $databaseConfig): BaseModel
    {
        /**
         * @var BaseModel
         */
        $selectedBase = new $fullClassName();
        $selectedBase->initialize($databaseConfig);
        $selectedBase->setAutoFreeResult(true);
        return $selectedBase;
    }
}