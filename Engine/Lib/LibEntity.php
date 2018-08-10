<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Fail\FailBlock;
use TREngine\Engine\Core\CoreCacheSection;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreSecure;
use TREngine\Engine\Core\CoreSql;
use TREngine\Engine\Core\CoreTable;
use Throwable;

/**
 * Gestionnaire d'entités.
 *
 * @author Sébastien Villemain
 */
abstract class LibEntity
{

    /**
     * Entités chargées.
     *
     * @var LibEntityData[]
     */
    private $entityDatas = array();

    /**
     * Retourne les informations du block cible via son type.
     *
     * @param string $blockTypeName
     * @return LibBlockData Informations sur le block.
     */
    public function &getBlockDataByType(string $blockTypeName): LibBlockData
    {
        $blockId = $this->requestBlockId($blockTypeName);

        if ($blockId < 0) {
            CoreSecure::getInstance()->catchException(new FailBlock("invalid block type",
                                                                    15,
                                                                    array($blockTypeName)));
        }
        return $this->getEntityData($blockId);
    }

    /**
     * Retourne les informations de l'entité.
     *
     * @param int $entityId l'identifiant de l'entité.
     * @return LibEntityData Informations sur l'entité.
     */
    public function &getEntityData(int $entityId): LibEntityData
    {
        $entityData = null;

        if ($this->isInCache($entityId)) {
            $entityData = $this->getFromCache($entityId);
        } else {
            $dbRequest = false;
            $blockArrayDatas = $this->requestBlockData($entityId,
                                                       $dbRequest);

            // Injection des informations du block
            $entityData = new LibBlockData($blockArrayDatas,
                                           $dbRequest);
            $this->addInCache($entityData);
        }
        return $entityData;
    }

    /**
     * Compilation d'un block.
     *
     * @param LibBlockData $blockData
     * @param bool $checkModule
     */
    public function buildBlockData(LibBlockData $blockData,
                                   bool $checkModule): void
    {
        if ($blockData->isValid() && $blockData->canActive($checkModule)) {
            $this->fireBuildEntityData($blockData);
        }
    }

    /**
     * Retourne l'identifiant du block.
     *
     * @param string $blockTypeName
     * @return int
     */
    private function &requestBlockId(string $blockTypeName): int
    {
        $blockId = -1;

        if (!empty($this->entityDatas)) {
            foreach ($this->entityDatas as $blockData) {
                if ($blockData->getType() === $blockTypeName) {
                    $blockId = $blockData->getIdAsInt();
                    break;
                }
            }
        }

        if ($blockId < 0) {
            $blockId = $this->loadBlockId($blockTypeName);
        }
        return $blockId;
    }

    /**
     * Charge l'identifiant du block.
     *
     * @param string $blockTypeName
     * @return int
     */
    private function &loadBlockId(string $blockTypeName): int
    {
        $blockId = -1;
        $coreSql = CoreSql::getInstance();
        $coreSql->select(CoreTable::BLOCKS,
                         array("block_id"),
                         array("called_by_type = 1", "AND type =  '" . $blockTypeName . "'"));

        if ($coreSql->affectedRows() > 0) {
            $blockId = $coreSql->fetchArray()[0]['block_id'];
        }
        return $blockId;
    }

    /**
     * Création des informations sur le block.
     *
     * @param int $blockId
     * @param int $dbRequest
     * @return array
     */
    private function &requestBlockData(int $blockId,
                                       bool &$dbRequest): array
    {
        $blockArrayDatas = array();

        // Recherche dans le cache
        $coreCache = CoreCache::getInstance(CoreCacheSection::BLOCKS);

        if (!$coreCache->cached($blockId . ".php")) {
            $blockArrayDatas = $this->loadBlockDatas($blockId);
            $dbRequest = !empty($blockArrayDatas);

            if ($dbRequest) {
                // Mise en cache
                $coreCache = CoreCache::getInstance(CoreCacheSection::BLOCKS);
                $content = $coreCache->serializeData($blockArrayDatas);
                $coreCache->writeCache($blockId . ".php",
                                       $content);
            }
        } else {
            $blockArrayDatas = $coreCache->readCacheAsArray($blockId . ".php");
        }
        return $blockArrayDatas;
    }

    /**
     * Création des informations sur le block.
     *
     * @param int $blockId
     * @return array
     */
    private function &loadBlockDatas(int $blockId): array
    {
        $blockArrayDatas = array();

        $coreSql = CoreSql::getInstance();
        $coreSql->select(CoreTable::BLOCKS,
                         array("block_id",
                    "side",
                    "position",
                    "title",
                    "type",
                    "rank",
                    "all_modules"),
                         array("block_id =  '" . $blockId . "'"));

        if ($coreSql->affectedRows() > 0) {
            $blockArrayDatas = $coreSql->fetchArray()[0];
            $blockArrayDatas['module_ids'] = array();
            $blockArrayDatas['block_config'] = array();

            $coreSql->select(CoreTable::BLOCKS_VISIBILITY,
                             array("module_id"),
                             array("block_id =  '" . $blockId . "'"));

            if ($coreSql->affectedRows() > 0) {
                $blockArrayDatas['module_ids'] = $coreSql->fetchArray();
            }

            $coreSql->select(CoreTable::BLOCKS_CONFIGS,
                             array("name", "value"),
                             array("block_id =  '" . $blockId . "'"));

            if ($coreSql->affectedRows() > 0) {
                $blockArrayDatas['block_config'] = $coreSql->fetchArray();
            }
        }
        return $blockArrayDatas;
    }

    /**
     * Alimente le cache des entités.
     *
     * @param LibEntityData $entityData
     */
    private function addInCache(LibEntityData &$entityData): void
    {
        $this->entityDatas[$entityData->getId()] = $entityData;
    }

    /**
     * Retourne les données de l'entité depuis le cache.
     *
     * @param int $entityId
     * @return LibEntityData
     */
    public function &getFromCache(int $entityId): LibEntityData
    {
        return $this->entityDatas[$entityId];
    }

    /**
     * Détermine si le cache contient les données de l'entité.
     *
     * @param int $entityId
     * @return bool
     */
    public function &isInCache(int $entityId): bool
    {
        $rslt = isset($this->entityDatas[$entityId]);
        return $rslt;
    }

    /**
     * Lance la compilation d'une entité.
     *
     * @param LibEntityData $entityData
     */
    private function fireBuildEntityData(LibEntityData &$entityData): void
    {
        $fullClassName = $entityData->getFullQualifiedClassName();
        $loaded = CoreLoader::classLoader($fullClassName);

        if ($loaded) {
            if ($entityData->isCallableViewMethod()) {
                try {
                    $entityModelInstance = $entityData->getNewEntityModel();

                    if ($entityModelInstance->isInViewList($entityData->getView())) {
                        $this->onBuildBegin($entityData);

                        ob_start();
                        $entityModelInstance->display($entityData->getView());
                        $entityData->setTemporyOutputBuffer(ob_get_clean());

                        $this->onBuildEnded($entityData);
                    } else {
                        $this->onViewParameterNotFound($entityData);
                    }
                } catch (Throwable $ex) {
                    CoreSecure::getInstance()->catchException($ex);
                }
            } else {
                $this->onViewMethodNotFound($entityData);
            }
        } else {
            $this->onEntityNotFound($entityData);
        }
    }

    abstract protected function onEntityNotFound(LibEntityData &$entityData): void;

    abstract protected function onViewMethodNotFound(LibEntityData &$entityData): void;

    abstract protected function onViewParameterNotFound(LibEntityData &$entityData): void;

    abstract protected function onBuildBegin(LibEntityData &$entityData): void;

    abstract protected function onBuildEnded(LibEntityData &$entityData): void;
}