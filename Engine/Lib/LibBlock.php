<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Fail\FailBlock;
use TREngine\Engine\Fail\FailBase;
use TREngine\Engine\Core\CoreAccessRank;
use TREngine\Engine\Core\CoreCacheSection;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Core\CoreSecure;
use TREngine\Engine\Core\CoreLayout;
use TREngine\Engine\Core\CoreSql;
use TREngine\Engine\Core\CoreTable;
use TREngine\Engine\Core\CoreTranslate;
use Throwable;

/**
 * Gestionnaire de blocks.
 *
 * @author Sébastien Villemain
 */
class LibBlock
{

    /**
     * Nom du fichier cache de bannissement.
     *
     * @var string
     */
    private const BLOCKS_INDEXER_FILENAME = "blocks_indexer.php";

    /**
     * Nom du fichier listant les blocks.
     *
     * @var string
     */
    private const BLOCKS_FILELISTER = CoreLoader::BLOCK_FILE . "s";

    /**
     * Gestionnnaire de blocks.
     *
     * @var LibBlock
     */
    private static $libBlock = null;

    /**
     * Blocks chargés.
     *
     * @var LibBlockData[]
     */
    private $blockDatas = array();

    private function __construct()
    {

    }

    /**
     * Instance du gestionnaire de block.
     *
     * @return LibBlock
     */
    public static function &getInstance(): LibBlock
    {
        if (self::$libBlock === null) {
            self::$libBlock = new LibBlock();
        }
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
                $this->buildBlockById($blockRawInfo['block_id'],
                                      true);
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
        $buffer = "";

        if ($selectedSide >= CoreLayout::BLOCK_SIDE_NONE) {
            foreach ($this->blockDatas as $blockData) {
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
     * @return array array("numeric" => identifiant int, "letters" => nom de la position).
     */
    public static function &getSideList(): array
    {
        $sideList = array();

        foreach (CoreLayout::BLOCK_SIDE_LIST as $side) {
            $sideList[] = array(
                "numeric" => $side,
                "letters" => self::getSideNumericDescription($side)
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
            throw new FailBlock("invalid block side number",
                                FailBase::getErrorCodeName(16),
                                                           array($side));
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
            throw new FailBlock("invalid block side name",
                                FailBase::getErrorCodeName(16),
                                                           array($sideName));
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
     * Retourne les informations du block cible via son type.
     *
     * @param string $blockTypeName
     * @return LibBlockData Informations sur le block.
     * @throws FailBlock
     */
    public function &getBlockDataByType(string $blockTypeName): LibBlockData
    {
        $blockId = $this->requestBlockId($blockTypeName);

        if ($blockId < 0) {
            throw new FailBlock("invalid block type",
                                FailBase::getErrorCodeName(15),
                                                           array($blockTypeName));
        }
        return $this->getBlockData($blockId);
    }

    /**
     * Retourne les informations du block cible.
     *
     * @param int $blockId l'identifiant du block.
     * @return LibBlockData Informations sur le block.
     */
    public function &getBlockData(int $blockId): LibBlockData
    {
        $blockData = null;

        if (isset($this->blockDatas[$blockId])) {
            $blockData = $this->blockDatas[$blockId];
        } else {
            $dbRequest = false;
            $blockArrayDatas = $this->requestBlockData($blockId,
                                                       $dbRequest);

            // Injection des informations du block
            $blockData = new LibBlockData($blockArrayDatas,
                                          $dbRequest);
            $this->addBlockData($blockData);
        }
        return $blockData;
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
            $this->fireBuildBlockData($blockData);
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

        if (!empty($this->blockDatas)) {
            foreach ($this->blockDatas as $blockData) {
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
     * Alimente le cache des blocks.
     *
     * @param LibBlockData $blockData
     */
    private function addBlockData(LibBlockData $blockData): void
    {
        $this->blockDatas[$blockData->getId()] = $blockData;
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
            $coreSql = CoreSql::getInstance();

            $coreSql->select(CoreTable::BLOCKS,
                             array(
                        "block_id",
                        "side",
                        "type",
                        "rank"
                    ),
                             array(),
                             array(
                        "side",
                        "position"
            ));

            if ($coreSql->affectedRows() > 0) {
                $blocksIndexer = $coreSql->fetchArray();

                // Mise en cache
                $content = $coreCache->serializeData($blocksIndexer);
                $coreCache->writeCache("blocks_indexer.php",
                                       $content);
            }
        } else {
            $blocksIndexer = $coreCache->readCacheAsArray(self::BLOCKS_INDEXER_FILENAME);
        }
        return $blocksIndexer;
    }

    /**
     * Compilation d'un block via son identifiant.
     *
     * @param int $blockId
     * @param bool $checkModule
     */
    private function buildBlockById(int $blockId,
                                    bool $checkModule): void
    {
        $blockData = $this->getBlockData($blockId);
        $this->buildBlockData($blockData,
                              $checkModule);
    }

    /**
     * Lance la compilation du block.
     *
     * @param LibBlockData $blockData
     */
    private function fireBuildBlockData(LibBlockData &$blockData): void
    {
        $blockFullClassName = $blockData->getFullQualifiedClassName();
        $loaded = CoreLoader::classLoader($blockFullClassName);

        if ($loaded) {
            if ($blockData->isCallableViewMethod()) {
                CoreTranslate::getInstance()->translate($blockData->getFolderName());

                $blockClass = $blockData->getNewEntityModel();

                if ($blockClass->isInViewList($blockData->getView())) {
                    // Capture des données d'affichage
                    ob_start();
                    $blockClass->display($blockData->getView());
                    $blockData->setTemporyOutputBuffer(ob_get_clean());
                }
            } else {
                CoreLogger::addError(FailBase::getErrorCodeDescription(FailBase::getErrorCodeName(25)) . " (" . $blockData->getType() . ")");
            }
        } else {
            CoreLogger::addError(FailBase::getErrorCodeDescription(FailBase::getErrorCodeName(25)) . " (" . $blockData->getType() . ")");
        }
    }
}