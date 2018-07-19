<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Fail\FailBlock;
use TREngine\Engine\Core\CoreAccessRank;
use TREngine\Engine\Core\CoreCacheSection;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Core\CoreRequest;
use TREngine\Engine\Core\CoreSecure;
use TREngine\Engine\Core\CoreLayout;
use TREngine\Engine\Core\CoreSql;
use TREngine\Engine\Core\CoreTable;
use TREngine\Engine\Core\CoreTranslate;
use TREngine\Engine\Core\CoreUrlRewriting;
use TREngine\Engine\Exec\ExecUtils;
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

    /**
     * Liste des blocks qui peuvent être appelés en standalone.
     *
     * @var array
     */
    private $standaloneBlocksType = array();

    private function __construct()
    {
        $this->addStandaloneBlockType("Login");
        $this->addStandaloneBlockType("ImageGenerator");
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
     * Ajout d'un type de block qui peut être appelé en standalone.
     *
     * @param string $blockTypeName
     */
    public function addStandaloneBlockType(string $blockTypeName): void
    {
        $this->standaloneBlocksType[] = $blockTypeName;
    }

    /**
     * Détermine si le block est utilisable en standalone.
     *
     * @param string $blockTypeName
     * @return bool
     */
    public function isStandaloneBlockType(string $blockTypeName): bool
    {
        return !empty($blockTypeName) && (array_search($blockTypeName,
                                                       $this->standaloneBlocksType) !== false);
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
     * Compilation du block demandé depuis une requête.
     */
    public function buildBlockRequested(): void
    {
        $blockId = CoreRequest::getInteger(CoreLayout::REQUEST_BLOCKID,
                                           -1);

        if ($blockId >= 0) {
            $this->buildBlockById($blockId,
                                  false);
        } else {
            $blockType = CoreRequest::getString(CoreLayout::REQUEST_BLOCKTYPE);
            $this->buildStandaloneBlockType($blockType);
        }
    }

    /**
     * Compilation d'un block via son type.
     *
     * @param string $blockTypeName
     */
    public function buildStandaloneBlockType(string $blockTypeName): void
    {
        if (!$this->isStandaloneBlockType($blockTypeName)) {
            CoreSecure::getInstance()->catchException(new FailBlock("invalid block type",
                                                                    15,
                                                                    array($blockTypeName)));
        }

        $empty = array(
            "block_id" => 1,
            "type" => $blockTypeName,
            "side" => CoreLayout::BLOCK_SIDE_RIGHT,
            "all_modules" => 1,
            "title" => $blockTypeName
        );
        $blockData = new LibBlockData($empty);
        $this->addBlockData($blockData);
        $this->buildBlockData($blockData,
                              false);
    }

    /**
     * Retourne les blocks compilés via le nom de leurs positions.
     *
     * @param string $sideName
     * @return string
     */
    public function &getBlocksBuildedBySideName(string $sideName): string
    {
        $sideNumeric = self::getSideAsNumeric($sideName);
        return $this->getBlocksBuildedBySidePosition($sideNumeric);
    }

    /**
     * Retourne le premier block compilé.
     *
     * @return string
     */
    public function &getFirstBlockBuilded(): string
    {
        return $this->getBlocksBuildedBySidePosition(CoreLayout::BLOCK_SIDE_FIRST_BUILDED);
    }

    /**
     * Liste des orientations possible.
     *
     * @return array array("numeric" => identifiant int, "letters" => nom de la position).
     */
    public static function &getSideList(): array
    {
        $sideList = array();

        foreach (CoreLayout::BLOCK_SIDE_LIST as $sideNumeric) {
            $sideList[] = array(
                "numeric" => $sideNumeric,
                "letters" => self::getSideAsLitteral($sideNumeric)
            );
        }
        return $sideList;
    }

    /**
     * Retourne le type d'orientation avec la traduction.
     *
     * @param mixed $side
     * @return string postion traduit (si possible).
     */
    public static function &getSideAsLitteral($side): string
    {
        // Assignation par défaut
        $litteralSideName = $side;

        if (is_numeric($side)) {
            $litteralSideName = strtoupper(self::getSideAsLetters($side));
        }

        $litteralSideName = defined($litteralSideName) ? constant($litteralSideName) : $litteralSideName;
        return $litteralSideName;
    }

    /**
     * Retourne le type d'orientation/postion en lettres.
     *
     * @param int $side
     * @return string identifiant de la position (right, left...).
     */
    public static function &getSideAsLetters(int $side): string
    {
        $sideLetters = array_search($side,
                                    CoreLayout::BLOCK_SIDE_LIST);

        if ($sideLetters === false) {
            CoreSecure::getInstance()->catchException(new FailBlock("invalid block side number",
                                                                    16,
                                                                    array($side)));
        }
        return $sideLetters;
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
     * Retourne les blocks compilés via leurs positions.
     *
     * @param int $selectedSide
     * @return string
     */
    private function &getBlocksBuildedBySidePosition(int $selectedSide): string
    {
        $buffer = "";

        if ($selectedSide === CoreLayout::BLOCK_SIDE_FIRST_BUILDED || $selectedSide >= CoreLayout::BLOCK_SIDE_NONE) {
            foreach ($this->blockDatas as $blockData) {
                if ($selectedSide >= CoreLayout::BLOCK_SIDE_NONE && $blockData->getSide() !== $selectedSide) {
                    continue;
                }

                $currentBuffer = $blockData->getBuffer();

                // Recherche le parametre indiquant qu'il doit y avoir une réécriture du buffer
                if (ExecUtils::inArray("rewriteBuffer",
                                       $blockData->getConfigs(),
                                       false)) {
                    $currentBuffer = CoreUrlRewriting::getInstance()->rewriteBuffer($currentBuffer);
                }

                $buffer .= $currentBuffer;

                if ($selectedSide === CoreLayout::BLOCK_SIDE_FIRST_BUILDED) {
                    break;
                }
            }
        }
        return $buffer;
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
     * Compilation d'un block.
     *
     * @param LibBlockData $blockData
     * @param bool $checkModule
     */
    private function buildBlockData(LibBlockData $blockData,
                                    bool $checkModule): void
    {
        if ($blockData->isValid() && $blockData->canActive($checkModule)) {
            $this->fireBuildBlockData($blockData);
        }
    }

    /**
     * Lance la compilation du block.
     *
     * @param LibBlockData $blockData
     */
    private function fireBuildBlockData(LibBlockData &$blockData): void
    {
        $blockClassName = CoreLoader::getFullQualifiedClassName($blockData->getClassName(),
                                                                $blockData->getFolderName());
        $loaded = CoreLoader::classLoader($blockClassName);

        // Vérification du block
        if ($loaded && CoreLoader::isCallable($blockClassName,
                                              "display")) {
            CoreTranslate::getInstance()->translate($blockData->getFolderName());

            try {
                /**
                 * @var BlockModel
                 */
                $blockClass = new $blockClassName();
                $blockClass->setBlockData($blockData);

                // Capture des données d'affichage
                ob_start();
                $blockClass->display();
                $blockData->setBuffer(ob_get_clean());
            } catch (Throwable $ex) {
                CoreSecure::getInstance()->catchException($ex);
            }
        } else {
            CoreLogger::addError(ERROR_BLOCK_CODE . " (" . $blockData->getType() . ")");
        }
    }

    /**
     * Retourne le type d'orientation/position en chiffre.
     *
     * @param string $sideName
     * @return int identifiant de la position (1, 2..).
     */
    private static function &getSideAsNumeric(string $sideName): int
    {
        if (!isset(CoreLayout::BLOCK_SIDE_LIST[$sideName])) {
            CoreSecure::getInstance()->catchException(new FailBlock("invalid block side name",
                                                                    16,
                                                                    array($sideName)));
        }

        $sideNumeric = CoreLayout::BLOCK_SIDE_LIST[$sideName];
        return $sideNumeric;
    }
}