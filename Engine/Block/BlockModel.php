<?php

namespace PassionEngine\Engine\Block;

use PassionEngine\Engine\Core\CoreLogger;
use PassionEngine\Engine\Lib\LibBlockData;
use PassionEngine\Engine\Lib\LibEntityModel;
use PassionEngine\Engine\Fail\FailBlock;

/**
 * Block de base, hérité par tous les autres blocks.
 * Modèle pour le contenu d'un block.
 *
 * @author Sébastien Villemain
 */
abstract class BlockModel extends LibEntityModel
{

    /**
     * {@inheritDoc}
     */
    public function display(string $view): void
    {
        unset($view);
        CoreLogger::addUserAlert(FailBase::getErrorCodeDescription(FailBase::getErrorCodeName(25)) . ((!empty($this->getBlockData()->getTitle())) ? ' (' . $this->getBlockData()->getTitle() . ')' : ''));
    }

    /**
     * {@inheritDoc}
     */
    public function setting(): void
    {
        throw new FailBlock('Invalid setting method');
    }

    /**
     * {@inheritDoc}
     */
    public function install(): void
    {
        throw new FailBlock('Invalid install method');
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(): void
    {
        throw new FailBlock('Invalid uninstall method');
    }

    /**
     * Affecte les données du block.
     *
     * @param LibBlockData $data
     */
    public function setBlockData(LibBlockData &$data): void
    {
        $this->setEntityData($data);
    }

    /**
     * Retourne le données du block.
     *
     * @return LibBlockData
     */
    public function &getBlockData(): LibBlockData
    {
        if (!$this->hasEntityData()) {
            $empty = array();
            $this->setEntityData(new LibBlockData($empty));
        }
        return $this->getEntityData();
    }
}