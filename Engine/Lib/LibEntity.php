<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Fail\FailBlock;
use TREngine\Engine\Core\CoreCacheSection;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreSecure;
use TREngine\Engine\Core\CoreSql;
use TREngine\Engine\Core\CoreTable;
use TREngine\Engine\Fail\FailEngine;
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
     * Lance une exception gérant ce type d'entité.
     *
     * @param string $message
     * @param string $failCode
     * @param array $failArgs
     * @throws FailEngine
     */
    protected function throwException(string $message,
                                      string $failCode = "",
                                      array $failArgs = array()): void
    {
        throw new FailEngine($message,
                             $failCode,
                             $failArgs);
    }

    /**
     * Retourne les informations de l'entité via son nom.
     *
     * @param string $entityFolderName Nom de l'entité.
     * @return LibBlockData Informations sur l'entité.
     */
    protected function &getEntityDataByFolderName(string $entityFolderName): LibEntityData
    {
        $entityId = $this->requestEntityId($entityFolderName);

        if ($entityId < 0) {
            $this->throwException("invalid entity folder name",
                                  FailBase::getErrorCodeName(15),
                                                             array($entityFolderName));
        }
        return $this->getEntityData($entityId);
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

        if ($this->cached($entityId)) {
            $entityData = $this->getCache($entityId);
        } else {
            $dbRequest = false;
            $blockArrayDatas = $this->requestBlockData($entityId,
                                                       $dbRequest);

            // Injection des informations du block
            $entityData = new LibBlockData($blockArrayDatas,
                                           $dbRequest);
            $this->addCache($entityData);
        }
        return $entityData;
    }

    /**
     * Compilation de l'entité.
     *
     * @param LibEntityData $entityData
     */
    public function buildEntityData(LibEntityData $entityData): void
    {
        if ($entityData->isValid() && $entityData->canUse()) {
            $this->fireBuildEntityData($entityData);
        }
    }

    /**
     * Retourne l'identifiant de l'entité.
     *
     * @param string $entityFolderName
     * @return int
     */
    private function &requestEntityId(string $entityFolderName): int
    {
        $entityId = -1;

        if (!empty($this->entityDatas)) {
            foreach ($this->entityDatas as $entityData) {
                if ($entityData->getFolderName() === $entityFolderName) {
                    $entityId = $entityData->getId();
                    break;
                }
            }
        }

        if ($entityId < 0) {
            $entityId = $this->loadBlockId($entityFolderName);
        }
        return $entityId;
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
        $coreSql = CoreSql::getInstance()->getSelectedBase();
        $coreSql->select(CoreTable::BLOCKS,
                         array("block_id"),
                         array("called_by_type = 1", "AND type =  '" . $blockTypeName . "'"))->query();

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

        $coreSql = CoreSql::getInstance()->getSelectedBase();
        $coreSql->select(CoreTable::BLOCKS,
                         array("block_id",
                    "side",
                    "position",
                    "title",
                    "type",
                    "rank",
                    "all_modules"),
                         array("block_id =  '" . $blockId . "'"))->query();

        if ($coreSql->affectedRows() > 0) {
            $blockArrayDatas = $coreSql->fetchArray()[0];
            $blockArrayDatas['module_ids'] = array();
            $blockArrayDatas['block_config'] = array();

            $coreSql->select(CoreTable::BLOCKS_VISIBILITY,
                             array("module_id"),
                             array("block_id =  '" . $blockId . "'"))->query();

            if ($coreSql->affectedRows() > 0) {
                $blockArrayDatas['module_ids'] = $coreSql->fetchArray();
            }

            $coreSql->select(CoreTable::BLOCKS_CONFIGS,
                             array("name", "value"),
                             array("block_id =  '" . $blockId . "'"))->query();

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
    private function addCache(LibEntityData &$entityData): void
    {
        $this->entityDatas[$entityData->getId()] = $entityData;
    }

    /**
     * Retourne les données de l'entité depuis le cache.
     *
     * @param int $entityId
     * @return LibEntityData
     */
    public function &getCache(int $entityId): LibEntityData
    {
        return $this->entityDatas[$entityId];
    }

    /**
     * Détermine si le cache contient les données de l'entité.
     *
     * @param int $entityId
     * @return bool
     */
    public function &cached(int $entityId): bool
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