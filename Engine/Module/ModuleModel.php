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
use TREngine\Engine\Fail\FailBase;

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
        CoreLogger::addError(FailBase::getErrorCodeDescription(FailBase::getErrorCodeName(24)) . ((!empty($this->getModuleData()->getName())) ? " (" . $this->getModuleData()->getName() . ")" : ""));
    }

    /**
     * {@inheritDoc}
     */
    public function setting(): void
    {
        throw new FailModule("Invalid setting method",
                             FailBase::getErrorCodeName(24));
    }

    /**
     * {@inheritDoc}
     */
    public function install(): void
    {
        $coreSql = CoreSql::getInstance()->getSelectedBase();
        $coreSql->insert(
                CoreTable::MODULES,
                array("name", "rank"),
                array($this->getModuleData()->getName(), 0))->query();
        $moduleId = $coreSql->insertId();
        $coreSql->insert(
                CoreTable::MODULES_CONFIGS,
                array("module_id", "name", "value"),
                array($moduleId, "key", "value"))->query();
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(): void
    {
        CoreSql::getInstance()->getSelectedBase()->delete(CoreTable::MODULES,
                                                          array("module_id = '" . $this->getModuleData()->getId() . "'"))->query();

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