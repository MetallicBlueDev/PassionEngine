<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Fail\FailEngine;
use TREngine\Engine\Fail\FailBase;

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
     * @return LibEntityData Informations sur l'entité.
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
            $entityArrayDatas = $this->requestEntityData($entityId);
            $entityData = $this->createEntityData($entityArrayDatas);
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
     * Charge l'identifiant de l'entité.
     *
     * @param string $entityFolderName
     * @return int
     */
    abstract protected function &loadEntityId(string $entityFolderName): int;

    /**
     * Retourne le nom de la section du cache à utiliser.
     */
    abstract protected function getCacheSectionName(): string;

    /**
     * Chargement des informations sur l'entité.
     *
     * @param int $entityId Identifiant de l'entité à charger.
     * @return array Informations sur l'entité.
     */
    abstract protected function &loadEntityDatas(int $entityId): array;

    /**
     * Création de l'instance de l'entité.
     *
     * @param array $entityArrayDatas Informations sur l'entité.
     * @return LibEntityData
     */
    abstract protected function &createEntityData(array $entityArrayDatas): LibEntityData;

    /**
     * Signale le début de compilation de l'entité.
     */
    abstract protected function onBuildBegin(LibEntityData &$entityData): void;

    /**
     * Signale la fin de la compilation de l'entité.
     */
    abstract protected function onBuildEnded(LibEntityData &$entityData): void;

    /**
     * Signale que la classe de l'entité n'a pas été trouvée.
     */
    abstract protected function onEntityNotFound(LibEntityData &$entityData): void;

    /**
     * Signale que la méthode d'affichage dans la classe de l'entité n'a pas été trouvée.
     */
    abstract protected function onViewMethodNotFound(LibEntityData &$entityData): void;

    /**
     * Signale que le paramètre d'affichage n'a pas été trouvée.
     */
    abstract protected function onViewParameterNotFound(LibEntityData &$entityData): void;

    /**
     * Retourne l'identifiant de l'entité.
     *
     * @param string $entityFolderName
     * @return int
     */
    private function &requestEntityId(string $entityFolderName): int
    {
        $entityId = $this->requestEntityIdByCache($entityFolderName);

        if ($entityId < 0) {
            $entityId = $this->loadEntityId($entityFolderName);
        }
        return $entityId;
    }

    /**
     * Retourne l'identifiant de l'entité via le cache.
     *
     * @param string $entityFolderName
     * @return int Retourne -1 si l'entité n'a pas été trouvée.
     */
    private function &requestEntityIdByCache(string $entityFolderName): int
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
        return $entityId;
    }

    /**
     * Demande le chargement des informations sur l'entité.
     *
     * @param int $entityId
     * @return array
     */
    private function &requestEntityData(int $entityId): array
    {
        $entityArrayDatas = array();

        // Recherche dans le cache
        $coreCache = CoreCache::getInstance($this->getCacheSectionName());
        $cacheFileName = $entityId . ".php";

        if (!$coreCache->cached($cacheFileName)) {
            $entityArrayDatas = $this->loadEntityDatas($entityId);

            if (!empty($entityArrayDatas)) {
                // Mise en cache
                $content = $coreCache->serializeData($entityArrayDatas);
                $coreCache->writeCache($cacheFileName,
                                       $content);
            }
        } else {
            $entityArrayDatas = $coreCache->readCacheAsArray($cacheFileName);
        }
        return $entityArrayDatas;
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
}