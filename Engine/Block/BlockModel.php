<?php

namespace TREngine\Engine\Block;

use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Lib\LibBlockData;
use TREngine\Engine\Lib\LibEntityModel;
use TREngine\Engine\Fail\FailBlock;

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
        CoreLogger::addError(ERROR_BLOCK_IMPLEMENT . ((!empty($this->getBlockData()->getTitle())) ? " (" . $this->getBlockData()->getTitle() . ")" : ""));
    }

    /**
     * {@inheritDoc}
     */
    public function setting(): void
    {
        throw new FailBlock("Invalid setting method");
    }

    /**
     * {@inheritDoc}
     */
    public function install(): void
    {
        throw new FailBlock("Invalid install method");
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(): void
    {
        throw new FailBlock("Invalid uninstall method");
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