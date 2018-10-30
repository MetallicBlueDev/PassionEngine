<?php

namespace PassionEngine\Engine\Lib;

use PassionEngine\Engine\Fail\FailBlock;
use PassionEngine\Engine\Fail\FailBase;
use PassionEngine\Engine\Core\CoreAccessRank;
use PassionEngine\Engine\Core\CoreCacheSection;
use PassionEngine\Engine\Core\CoreCache;
use PassionEngine\Engine\Core\CoreLoader;
use PassionEngine\Engine\Core\CoreLogger;
use PassionEngine\Engine\Core\CoreLayout;
use PassionEngine\Engine\Core\CoreSql;
use PassionEngine\Engine\Core\CoreTable;
use PassionEngine\Engine\Core\CoreTranslate;
use PassionEngine\Engine\Exec\ExecUtils;

/**
 * Gestionnaire de blocks.
 *
 * @author Sébastien Villemain
 */
class LibBlock extends LibEntity
{

    /**
     * Nom du fichier cache de bannissement.
     *
     * @var string
     */
    private const BLOCKS_INDEXER_FILENAME = 'blocks_indexer.php';

    /**
     * Nom du fichier listant les blocks.
     *
     * @var string
     */
    private const BLOCKS_FILELISTER = CoreLoader::BLOCK_FILE . 's';

    /**
     * Gestionnaire de blocks.
     *
     * @var LibBlock
     */
    private static $libBlock = null;

    private function __construct()
    {

    }

    /**
     * Vérification de l'instance du gestionnaire des blocks.
     */
    public static function checkInstance(): void
    {
        if (self::$libBlock === null) {
            self::$libBlock = new LibBlock();
        }
    }

    /**
     * Instance du gestionnaire de blocks.
     *
     * @return LibBlock
     */
    public static function &getInstance(): LibBlock
    {
        self::checkInstance();
        return self::$libBlock;
    }

    /**
     * Compilation de tous les blocks.
     */
    public function buildAllBlocks(): void
    {
        // Recherche dans l'indexeur
        $blocksIndexer = $this->getBlocksIndexer();

        foreach ($blocksIndexer as $blockRawInfo) {
            if ($blockRawInfo['side'] > CoreLayout::BLOCK_SIDE_NONE && $blockRawInfo['rank'] >= CoreAccessRank::NONE) {
                $this->buildEntityDataById($blockRawInfo['block_id']);
            }
        }
    }

    /**
     * Retourne les blocks compilés via leurs positions.
     *
     * @param int $selectedSide
     * @return string
     */
    public function &getBlocksBuildedBySide(int $selectedSide): string
    {
        $buffer = '';

        if ($selectedSide >= CoreLayout::BLOCK_SIDE_NONE) {
            foreach ($this->getEntityDatas() as $blockData) {
                if ($blockData->getSide() !== $selectedSide) {
                    continue;
                }

                $buffer .= $blockData->getFinalOutput();
            }
        }
        return $buffer;
    }

    /**
     * Liste des positions possibles.
     *
     * @return array array('numeric' => identifiant int, 'letters' => nom de la position).
     */
    public static function &getSideList(): array
    {
        $sideList = array();

        foreach (CoreLayout::BLOCK_SIDE_LIST as $side) {
            $sideList[] = array(
                'numeric' => $side,
                'letters' => self::getSideNumericDescription($side)
            );
        }
        return $sideList;
    }

    /**
     * Retourne la description de la position.
     *
     * @param int $side
     * @return string Description de la position.
     */
    public static function &getSideNumericDescription(int $side): string
    {
        $sideName = self::getSideAsLetters($side);
        return self::getSideNameDescription($sideName);
    }

    /**
     * Retourne la description de la position.
     *
     * @param string $sideName
     * @return string Description de la position.
     */
    public static function &getSideNameDescription(string $sideName): string
    {
        $sideDescription = strtoupper($sideName);
        $sideDescription = defined($sideDescription) ? constant($sideDescription) : $sideDescription;
        return $sideDescription;
    }

    /**
     * Retourne le nom de position.
     *
     * @param int $side Identifiant de la position (1, 2, ...).
     * @return string Le nom de la position (right, left...).
     * @throws FailBlock
     */
    public static function &getSideAsLetters(int $side): string
    {
        $sideName = array_search($side,
                                 CoreLayout::BLOCK_SIDE_LIST);

        if ($sideName === false) {
            throw new FailBlock('invalid block side number',
                                FailBase::getErrorCodeName(16),
                                                           array($side),
                                                           true);
        }
        return $sideName;
    }

    /**
     * Retourne l'identifiant de la position.
     *
     * @param string $sideName Nom de la position (right, left...).
     * @return int Identifiant de la position (1, 2..).
     * @throws FailBlock
     */
    public static function &getSideAsNumeric(string $sideName): int
    {
        if (!isset(CoreLayout::BLOCK_SIDE_LIST[$sideName])) {
            throw new FailBlock('invalid block side name',
                                FailBase::getErrorCodeName(16),
                                                           array($sideName),
                                                           true);
        }

        $side = CoreLayout::BLOCK_SIDE_LIST[$sideName];
        return $side;
    }

    /**
     * Retourne la liste des blocks disponibles.
     *
     * @return array
     */
    public static function &getBlockList(): array
    {
        return CoreCache::getInstance()->getFileList(self::BLOCKS_FILELISTER,
                                                     CoreLoader::BLOCK_FILE);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $message
     * @param string $failCode
     * @param array $failArgs
     * @return void
     * @throws FailBlock
     */
    protected function throwException(string $message,
                                      string $failCode = '',
                                      array $failArgs = array()): void
    {
        throw new FailBlock($message,
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
        $blockId = -1;
        $coreSql = CoreSql::getInstance()->getSelectedBase();
        $coreSql->select(CoreTable::BLOCKS,
                         array('block_id'),
                         array('called_by_type = 1', 'AND type =  \'' . $entityFolderName . '\''))->query();

        if ($coreSql->affectedRows() > 0) {
            $blockId = $coreSql->fetchArray()[0]['block_id'];
        }
        return $blockId;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    protected function getCacheSectionName(): string
    {
        return CoreCacheSection::BLOCKS;
    }

    /**
     * {@inheritDoc}
     *
     * @param int $entityId
     * @return array
     */
    protected function &loadEntityDatas(int $entityId): array
    {
        $blockArrayDatas = array();

        $coreSql = CoreSql::getInstance()->getSelectedBase();
        $coreSql->select(CoreTable::BLOCKS,
                         array('block_id',
                'side',
                'position',
                'title',
                'type',
                'rank',
                'all_modules'),
                         array('block_id =  \'' . $entityId . '\''))->query();

        if ($coreSql->affectedRows() > 0) {
            $blockArrayDatas = $coreSql->fetchArray()[0];
            $blockArrayDatas['module_ids'] = array();
            $blockArrayDatas['block_config'] = array();

            $coreSql->select(CoreTable::BLOCKS_VISIBILITY,
                             array('module_id'),
                             array('block_id =  \'' . $entityId . '\''))->query();

            if ($coreSql->affectedRows() > 0) {
                $blockArrayDatas['module_ids'] = $coreSql->fetchArray();
            }

            $coreSql->select(CoreTable::BLOCKS_CONFIGS,
                             array('name', 'value'),
                             array('block_id =  \'' . $entityId . '\''))->query();

            if ($coreSql->affectedRows() > 0) {
                $blockArrayDatas['block_config'] = $coreSql->fetchArray();
                $blockArrayDatas['block_config'] = ExecUtils::getArrayConfigs($blockArrayDatas['block_config']);
            }
        }
        return $blockArrayDatas;
    }

    /**
     * {@inheritDoc}
     *
     * @param array $entityArrayDatas
     * @return LibEntityData
     */
    protected function &createEntityData(array $entityArrayDatas): LibEntityData
    {
        $blockData = new LibBlockData($entityArrayDatas);
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
        CoreLogger::addError(CoreTranslate::getConstantDescription(FailBase::getErrorCodeName(25),
                                                                                              array($entityData->getFolderName()),
                                                                                              true));
    }

    /**
     * {@inheritDoc}
     *
     * @param LibEntityData $entityData
     */
    protected function onViewMethodNotFound(LibEntityData &$entityData): void
    {
        CoreLogger::addError(CoreTranslate::getConstantDescription(FailBase::getErrorCodeName(25),
                                                                                              array($entityData->getFolderName()),
                                                                                              true));
    }

    /**
     * {@inheritDoc}
     *
     * @param LibEntityData $entityData
     */
    protected function onViewParameterNotFound(LibEntityData &$entityData): void
    {
        CoreLogger::addError(CoreTranslate::getConstantDescription(FailBase::getErrorCodeName(25),
                                                                                              array($entityData->getFolderName()),
                                                                                              true));
    }

    /**
     * Retourne un tableau définissant les blocks disponibles.
     *
     * @return array
     */
    private function getBlocksIndexer(): array
    {
        $blocksIndexer = array();
        $coreCache = CoreCache::getInstance(CoreCacheSection::BLOCKS);

        if (!$coreCache->cached(self::BLOCKS_INDEXER_FILENAME)) {
            $coreSql = CoreSql::getInstance()->getSelectedBase();
            $coreSql->select(CoreTable::BLOCKS,
                             array('block_id',
                    'side',
                    'type',
                    'rank'),
                             array(),
                             array('side',
                    'position'))->query();

            if ($coreSql->affectedRows() > 0) {
                $blocksIndexer = $coreSql->fetchArray();

                // Mise en cache
                $content = $coreCache->serializeData($blocksIndexer);
                $coreCache->writeCacheAsString('blocks_indexer.php',
                                               $content);
            }
        } else {
            $blocksIndexer = $coreCache->readCacheAsArray(self::BLOCKS_INDEXER_FILENAME);
        }
        return $blocksIndexer;
    }
}