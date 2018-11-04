<?php

namespace PassionEngine\Engine\Module;

use PassionEngine\Engine\Core\CoreLogger;
use PassionEngine\Engine\Core\CoreSql;
use PassionEngine\Engine\Core\CoreTable;
use PassionEngine\Engine\Core\CoreCache;
use PassionEngine\Engine\Core\CoreTranslate;
use PassionEngine\Engine\Core\CoreCacheSection;
use PassionEngine\Engine\Lib\LibEntityModel;
use PassionEngine\Engine\Lib\LibModuleData;
use PassionEngine\Engine\Fail\FailModule;
use PassionEngine\Engine\Fail\FailBase;

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
        CoreLogger::addUserAlert(CoreTranslate::getConstantDescription(FailBase::getErrorCodeName(24),
                                                                                              array($this->getModuleData()->getName()),
                                                                                              true));
    }

    /**
     * {@inheritDoc}
     */
    public function setting(): void
    {
        throw new FailModule('Invalid setting method',
                             FailBase::getErrorCodeName(24),
                                                        array($this->getModuleData()->getName()),
                                                        true);
    }

    /**
     * {@inheritDoc}
     */
    public function install(): void
    {
        $coreSql = CoreSql::getInstance()->getSelectedBase();
        $coreSql->insert(
            CoreTable::MODULES,
            array('name', 'rank'),
            array($this->getModuleData()->getName(), 0))->query();
        $moduleId = $coreSql->insertId();
        $coreSql->insert(
            CoreTable::MODULES_CONFIGS,
            array('module_id', 'name', 'value'),
            array($moduleId, 'key', 'value'))->query();
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(): void
    {
        CoreSql::getInstance()->getSelectedBase()->delete(CoreTable::MODULES,
                                                          array('module_id = \'' . $this->getModuleData()->getId() . '\''))->query();

        CoreCache::getInstance(CoreCacheSection::MODULES)->removeCache($this->getModuleData()->getName() . '.php');
        CoreTranslate::removeCache('modules' . DIRECTORY_SEPARATOR . $this->getModuleData()->getName());
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