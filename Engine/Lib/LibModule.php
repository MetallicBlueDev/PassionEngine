<?php

namespace PassionEngine\Engine\Lib;

use PassionEngine\Engine\Core\CoreCacheSection;
use PassionEngine\Engine\Core\CoreCache;
use PassionEngine\Engine\Core\CoreLoader;
use PassionEngine\Engine\Core\CoreLogger;
use PassionEngine\Engine\Core\CoreSql;
use PassionEngine\Engine\Core\CoreTable;
use PassionEngine\Engine\Core\CoreLayout;
use PassionEngine\Engine\Core\CoreTranslate;
use PassionEngine\Engine\Fail\FailModule;
use PassionEngine\Engine\Fail\FailBase;
use PassionEngine\Engine\Exec\ExecUtils;

/**
 * Gestionnaire de module.
 *
 * @author Sébastien Villemain
 */
class LibModule extends LibEntity
{

    /**
     * Nom du fichier listant les modules.
     *
     * @var string
     */
    private const MODULES_FILELISTER = CoreLoader::MODULE_FILE . "s";

    /**
     * Instance du gestionnaire de module.
     *
     * @var LibModule
     */
    private static $libModule = null;

    /**
     * Création du gestionnaire.
     */
    private function __construct()
    {

    }

    /**
     * Vérification de l'instance du gestionnaire des modules.
     */
    public static function checkInstance(): void
    {
        if (self::$libModule === null) {
            self::$libModule = new LibModule();
        }
    }

    /**
     * Instance du gestionnaire de modules.
     *
     * @return LibModule
     */
    public static function &getInstance(): LibModule
    {
        self::checkInstance();
        return self::$libModule;
    }

    /**
     * Retourne un tableau contenant les modules disponibles.
     *
     * @return array => array("value" => valeur du module, "name" => nom du module).
     */
    public static function &getModuleList(): array
    {
        $moduleList = array();
        $modules = CoreCache::getInstance()->getNameList(self::MODULES_FILELISTER);

        foreach ($modules as $module) {
            $moduleList[] = array(
                "value" => $module,
                "name" => "Module " . $module
            );
        }
        return $moduleList;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $message
     * @param string $failCode
     * @param array $failArgs
     * @return void
     * @throws FailModule
     */
    protected function throwException(string $message,
                                      string $failCode = "",
                                      array $failArgs = array()): void
    {
        throw new FailModule($message,
                             $failCode,
                             $failArgs);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $entityFolderName
     * @return int
     */
    protected function &loadEntityId(string $entityFolderName): int
    {
        $moduleId = -1;
        $coreSql = CoreSql::getInstance()->getSelectedBase();
        $coreSql->select(CoreTable::MODULES,
                         array("module_id"),
                         array("name =  '" . ucfirst($entityFolderName) . "'"))->query();

        if ($coreSql->affectedRows() > 0) {
            $moduleId = $coreSql->fetchArray()[0]['module_id'];
        }
        return $moduleId;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    protected function getCacheSectionName(): string
    {
        return CoreCacheSection::MODULES;
    }

    /**
     * {@inheritDoc}
     *
     * @param int $entityId
     * @return array
     */
    protected function &loadEntityDatas(int $entityId): array
    {
        $moduleArrayDatas = array();
        $coreSql = CoreSql::getInstance()->getSelectedBase();
        $coreSql->select(CoreTable::MODULES,
                         array("module_id", "name", "rank"),
                         array("module_id =  '" . $entityId . "'"))->query();

        if ($coreSql->affectedRows() > 0) {
            $moduleArrayDatas = $coreSql->fetchArray()[0];
            $moduleArrayDatas['module_config'] = array();

            $coreSql->select(CoreTable::MODULES_CONFIGS,
                             array("name", "value"),
                             array("module_id =  '" . $moduleArrayDatas['module_id'] . "'"))->query();

            if ($coreSql->affectedRows() > 0) {
                $moduleArrayDatas['module_config'] = $coreSql->fetchArray();
                $moduleArrayDatas['module_config'] = ExecUtils::getArrayConfigs($moduleArrayDatas['module_config']);
            }
        }
        return $moduleArrayDatas;
    }

    /**
     * {@inheritDoc}
     *
     * @param array $entityArrayDatas
     * @return LibEntityData
     */
    protected function &createEntityData(array $entityArrayDatas): LibEntityData
    {
        $blockData = new LibModuleData($entityArrayDatas);
        return $blockData;
    }

    /**
     * {@inheritDoc}
     *
     * @param LibEntityData $entityData
     */
    protected function onBuildBegin(LibEntityData &$entityData): void
    {
        CoreTranslate::getInstance()->translate($entityData->getFolderName());

        $this->updateCount($entityData->getId());


        $libBreadcrumb = LibBreadcrumb::getInstance();
        $libBreadcrumb->addTrail($entityData->getName(),
                                 "?" . CoreLayout::REQUEST_MODULE . "=" . $entityData->getName());

        // TODO A MODIFIER
        // Juste une petite exception pour le module management qui est different
        if ($entityData->getName() !== "management") {
            $libBreadcrumb->addTrail($entityData->getView(),
                                     "?" . CoreLayout::REQUEST_MODULE . "=" . $entityData->getName() . "&" . CoreLayout::REQUEST_VIEW . "=" . $entityData->getView());
        }
    }

    /**
     * {@inheritDoc}
     *
     * @param LibEntityData $entityData
     */
    protected function onBuildEnded(LibEntityData &$entityData): void
    {
        unset($entityData);
    }

    /**
     * {@inheritDoc}
     *
     * @param LibEntityData $entityData
     */
    protected function onEntityNotFound(LibEntityData &$entityData): void
    {
        CoreLogger::addError(CoreTranslate::getConstantDescription(FailBase::getErrorCodeName(24),
                                                                                              array($entityData->getName())),
                                                                                              true);
    }

    /**
     * {@inheritDoc}
     *
     * @param LibEntityData $entityData
     */
    protected function onViewMethodNotFound(LibEntityData &$entityData): void
    {
        CoreLogger::addError(CoreTranslate::getConstantDescription(FailBase::getErrorCodeName(24),
                                                                                              array($entityData->getName())),
                                                                                              true);
    }

    /**
     * {@inheritDoc}
     *
     * @param LibEntityData $entityData
     */
    protected function onViewParameterNotFound(LibEntityData &$entityData): void
    {
        CoreLogger::addError(CoreTranslate::getConstantDescription(FailBase::getErrorCodeName(24),
                                                                                              array($entityData->getName())),
                                                                                              true);
    }

    /**
     * Mise à jour du compteur de visite du module courant.
     *
     * @param int $moduleId
     */
    private function updateCount(int $moduleId): void
    {
        $coreSql = CoreSql::getInstance()->getSelectedBase();
        $coreSql->addQuotedValue("count + 1");
        $coreSql->update(CoreTable::MODULES,
                         array("count" => "count + 1"),
                         array("module_id = '" . $moduleId . "'"))->query();
    }
}