<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Base\BaseModel;
use TREngine\Engine\Fail\FailSql;

/**
 * Gestionnaire de la communication SQL.
 *
 * @author Sébastien Villemain
 */
class CoreSql extends CoreDriverSelector
{

    /**
     * Gestionnaire SQL.
     *
     * @var CoreSql
     */
    private static $coreSql = null;

    /**
     * Nouveau gestionnaire.
     *
     * @throws FailSql
     */
    private function __construct()
    {
        $this->createDriver();
    }

    /**
     * Destruction du gestionnaire.
     */
    public function __destruct()
    {
        parent::__destruct();
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
        return self::$coreSql !== null && self::$coreSql->getSelectedDriver() !== null;
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
        return $this->getSelectedDriver();
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    protected function getConfiguration(): array
    {
        return CoreMain::getInstance()->getConfigs()->getConfigDatabase();
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    protected function getFilePrefix(): string
    {
        return CoreLoader::BASE_FILE;
    }

    /**
     * {@inheritDoc}
     */
    protected function onInitialized(): void
    {
        $this->getSelectedBase()->setAutoFreeResult(true);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $message
     * @param string $failCode
     * @param array $failArgs
     * @throws FailSql
     */
    protected function throwException(string $message,
                                      string $failCode = "",
                                      array $failArgs = array()): void
    {
        throw new FailSql($message,
                          $failCode,
                          $failArgs);
    }
}