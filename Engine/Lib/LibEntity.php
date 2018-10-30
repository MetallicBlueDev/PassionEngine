<?php

namespace PassionEngine\Engine\Lib;

use PassionEngine\Engine\Core\CoreCache;
use PassionEngine\Engine\Core\CoreLoader;
use PassionEngine\Engine\Core\CoreTraitException;
use PassionEngine\Engine\Fail\FailBase;

/**
 * Gestionnaire d'entités.
 *
 * @author Sébastien Villemain
 */
abstract class LibEntity
{

    use CoreTraitException;

    /**
     * Entités chargées.
     *
     * @var LibEntityData[]
     */
    private $entityDatas = array();

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
     * Retourne les informations de l'entité via son nom.
     *
     * @param string $entityFolderName Nom de l'entité.
     * @return LibEntityData Informations sur l'entité.
     */
    public function &getEntityDataByFolderName(string $entityFolderName): LibEntityData
    {
        $entityId = $this->requestEntityId($entityFolderName);

        if ($entityId < 0) {
            $this->throwException('invalid entity folder name',
                                  FailBase::getErrorCodeName(15),
                                                             array($entityFolderName),
                                                             true);
        }
        return $this->getEntityData($entityId);
    }

    /**
     * Compilation d'une entité via son identifiant.
     *
     * @param int $entityId
     */
    public function buildEntityDataById(int $entityId): void
    {
        $entityData = $this->getEntityData($entityId);
        $this->buildEntityData($entityData);
    }

    /**
     * Compilation de l'entité.
     *
     * @param LibEntityData $entityData
     */
    public function buildEntityData(LibEntityData $entityData): void
    {
        if ($entityData->isValid()) {
            if ($this->loadEntity($entityData)) {
                if ($entityData->canUse()) {
                    $this->fireBuildEntityData($entityData);
                }
            } else {
                $this->onEntityNotFound($entityData);
            }
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
     * Retourne les entités actuellement en cache.
     *
     * @return LibEntityData[]
     */
    protected function &getEntityDatas(): array
    {
        return $this->entityDatas;
    }

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
        $cacheFileName = $entityId . '.php';

        if (!$coreCache->cached($cacheFileName)) {
            $entityArrayDatas = $this->loadEntityDatas($entityId);

            if (!empty($entityArrayDatas)) {
                // Mise en cache
                $content = $coreCache->serializeData($entityArrayDatas);
                $coreCache->writeCacheAsString($cacheFileName,
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
    private function &getCache(int $entityId): LibEntityData
    {
        return $this->entityDatas[$entityId];
    }

    /**
     * Détermine si le cache contient les données de l'entité.
     *
     * @param int $entityId
     * @return bool
     */
    private function &cached(int $entityId): bool
    {
        $rslt = isset($this->entityDatas[$entityId]);
        return $rslt;
    }

    /**
     * Chargement de l'entité (si nécessaire).
     *
     * @param LibEntityData $entityData
     * @return bool
     */
    private function &loadEntity(LibEntityData &$entityData): bool
    {
        $fullClassName = $entityData->getFullQualifiedClassName();
        return CoreLoader::classLoader($fullClassName);
    }

    /**
     * Lance la compilation d'une entité.
     *
     * @param LibEntityData $entityData
     */
    private function fireBuildEntityData(LibEntityData &$entityData): void
    {
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
    }
}