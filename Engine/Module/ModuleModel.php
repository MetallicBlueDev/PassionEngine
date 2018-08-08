<?php

namespace TREngine\Engine\Module;

use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Core\CoreSql;
use TREngine\Engine\Core\CoreTable;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreTranslate;
use TREngine\Engine\Core\CoreCacheSection;
use TREngine\Engine\Lib\LibEntityModel;
use TREngine\Engine\Lib\LibModuleData;
use TREngine\Engine\Fail\FailModule;

/**
 * Module de base, hérité par tous les autres modules.
 * Modèle pour le contenu d'un module.
 *
 * @author Sébastien Villemain
 */
abstract class ModuleModel extends LibEntityModel
{

    /**
     * {@inheritDoc}
     */
    public function display(string $view): void
    {
        unset($view);
        CoreLogger::addError(ERROR_MODULE_IMPLEMENT . ((!empty($this->getModuleData()->getName())) ? " (" . $this->getModuleData()->getName() . ")" : ""));
    }

    /**
     * {@inheritDoc}
     */
    public function setting(): void
    {
        throw new FailModule("Invalid setting method");
    }

    /**
     * {@inheritDoc}
     */
    public function install(): void
    {
        $coreSql = CoreSql::getInstance();
        $coreSql->insert(
                CoreTable::MODULES,
                array("name", "rank"),
                array($this->getModuleData()->getName(), 0)
        );
        $moduleId = $coreSql->insertId();
        CoreSql::getInstance()->insert(
                CoreTable::MODULES_CONFIGS,
                array("module_id", "name", "value"),
                array($moduleId, "key", "value")
        );
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(): void
    {
        CoreSql::getInstance()->delete(
                CoreTable::MODULES,
                array("module_id = '" . $this->getModuleData()->getId() . "'")
        );

        CoreCache::getInstance(CoreCacheSection::MODULES)->removeCache($this->getModuleData()->getName() . ".php");
        CoreTranslate::removeCache("modules" . DIRECTORY_SEPARATOR . $this->getModuleData()->getName());
    }

    /**
     * Affecte les données du module.
     *
     * @param LibModuleData $data
     */
    public function setModuleData(LibModuleData &$data): void
    {
        $this->setEntityData($data);
    }

    /**
     * Retourne le données du module.
     *
     * @return LibModuleData
     */
    public function &getModuleData(): LibModuleData
    {
        if (!$this->hasEntityData()) {
            $empty = array();
            $this->setEntityData(new LibModuleData($empty));
        }
        return $this->getEntityData();
    }
}