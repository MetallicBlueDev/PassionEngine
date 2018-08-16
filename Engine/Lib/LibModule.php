<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreAccess;
use TREngine\Engine\Core\CoreCacheSection;
use TREngine\Engine\Core\CoreAccessType;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Core\CoreSecure;
use TREngine\Engine\Core\CoreSession;
use TREngine\Engine\Core\CoreSql;
use TREngine\Engine\Core\CoreTable;
use TREngine\Engine\Core\CoreLayout;
use TREngine\Engine\Core\CoreTranslate;
use Throwable;

/**
 * Gestionnaire de module.
 *
 * @author Sébastien Villemain
 */
class LibModule
{

    /**
     * Nom du fichier listant les modules.
     *
     * @var string
     */
    private const MODULES_FILELISTER = CoreLoader::MODULE_FILE . "s";

    /**
     * Instance du gestionnaire de module.
     *
     * @var LibModule
     */
    private static $libModule = null;

    /**
     * Tableau d'information sur les modules.
     *
     * @var LibModuleData[]
     */
    private $moduleDatas = array();

    /**
     * Création du gestionnaire.
     */
    private function __construct()
    {

    }

    /**
     * Vérification de l'instance du gestionnaire des modules.
     */
    public static function checkInstance(): void
    {
        if (self::$libModule === null) {
            // Création d'un instance autonome
            self::$libModule = new LibModule();
        }
    }

    /**
     * Création et récuperation de l'instance du module.
     *
     * @return LibModule
     */
    public static function &getInstance(): LibModule
    {
        self::checkInstance();
        return self::$libModule;
    }

    /**
     * Retourne un tableau contenant les modules disponibles.
     *
     * @return array => array("value" => valeur du module, "name" => nom du module).
     */
    public static function &getModuleList(): array
    {
        $moduleList = array();
        $modules = CoreCache::getInstance()->getNameList(self::MODULES_FILELISTER);

        foreach ($modules as $module) {
            $moduleList[] = array(
                "value" => $module,
                "name" => "Module " . $module
            );
        }
        return $moduleList;
    }

    /**
     * Retourne les informations du module cible.
     *
     * @param string $moduleName Le nom du module, par défaut le module courant.
     * @return LibModuleData Informations sur le module.
     */
    public function &getModuleData(string $moduleName): LibModuleData
    {
        $moduleData = null;
        $moduleName = ucfirst($moduleName);

        if (isset($this->moduleDatas[$moduleName])) {
            $moduleData = $this->moduleDatas[$moduleName];
        } else {
            $dbRequest = false;
            $moduleArrayDatas = $this->requestModuleData($moduleName,
                                                         $dbRequest);

            // Injection des informations du module
            $moduleData = new LibModuleData($moduleArrayDatas,
                                            $dbRequest);
            $this->addModuleData($moduleData);
        }
        return $moduleData;
    }

    /**
     * Compilation du module demandé.
     *
     * @param LibModuleData $moduleData
     */
    public function buildModuleData(LibModuleData &$moduleData): void
    {
        // Vérification du niveau d'acces
        if (($moduleData->installed() && CoreAccess::autorize(CoreAccessType::getTypeFromToken($moduleData))) || (!$moduleData->installed() && CoreSession::getInstance()->getSessionData()->hasAdminRank())) {
            if ($moduleData->isValid()) {
                CoreTranslate::getInstance()->translate($moduleData->getFolderName());

                $libBreadcrumb = LibBreadcrumb::getInstance();
                $libBreadcrumb->addTrail($moduleData->getName(),
                                         "?" . CoreLayout::REQUEST_MODULE . "=" . $moduleData->getName());

                // TODO A MODIFIER
                // Juste une petite exception pour le module management qui est different
                if ($moduleData->getName() !== "management") {
                    $libBreadcrumb->addTrail($moduleData->getView(),
                                             "?" . CoreLayout::REQUEST_MODULE . "=" . $moduleData->getName() . "&" . CoreLayout::REQUEST_VIEW . "=" . $moduleData->getView());
                }

                $this->fireBuildModuleData($moduleData);
            }
        } else {
            CoreLogger::addError(ERROR_ACCES_ZONE . " " . CoreAccess::getAccessErrorMessage($moduleData));
        }
    }

    /**
     * Demande le chargement des informations du module.
     *
     * @param string $moduleName Le nom du module.
     * @return array Informations sur le module.
     */
    private function &requestModuleData(string $moduleName,
                                        bool & $dbRequest): array
    {
        $moduleArrayDatas = array();

        // Recherche dans le cache
        $coreCache = CoreCache::getInstance(CoreCacheSection::MODULES);

        if (!$coreCache->cached($moduleName . ".php")) {
            $moduleArrayDatas = $this->loadModuleDatas($moduleName);
            $dbRequest = !empty($moduleArrayDatas);

            if ($dbRequest) {
                // Mise en cache
                $content = $coreCache->serializeData($moduleArrayDatas);
                $coreCache->writeCache($moduleName . ".php",
                                       $content);
            }
        } else {
            $moduleArrayDatas = $coreCache->readCacheAsArray($moduleName . ".php");
        }
        return $moduleArrayDatas;
    }

    /**
     * Chargement des informations du module.
     *
     * @param string $moduleName Le nom du module.
     * @return array Informations sur le module.
     */
    private function &loadModuleDatas(string $moduleName): array
    {
        $moduleArrayDatas = array();
        $coreSql = CoreSql::getInstance();
        $coreSql->select(CoreTable::MODULES,
                         array("module_id", "name", "rank"),
                         array("name =  '" . $moduleName . "'"));

        if ($coreSql->affectedRows() > 0) {
            $moduleArrayDatas = $coreSql->fetchArray()[0];
            $moduleArrayDatas['module_config'] = array();

            $coreSql->select(CoreTable::MODULES_CONFIGS,
                             array("name", "value"),
                             array("module_id =  '" . $moduleArrayDatas['module_id'] . "'"
            ));

            if ($coreSql->affectedRows() > 0) {
                $moduleArrayDatas['module_config'] = $coreSql->fetchArray();
            }
        }
        return $moduleArrayDatas;
    }

    /**
     * Alimente le cache des modules.
     *
     * @param LibModuleData $moduleData
     */
    private function addModuleData(LibModuleData $moduleData): void
    {
        $this->moduleDatas[$moduleData->getName()] = $moduleData;
    }

    /**
     * Compilation du module.
     *
     * @param LibModuleData $moduleData
     */
    private function fireBuildModuleData(LibModuleData &$moduleData): void
    {
        $fullClassName = $moduleData->getFullQualifiedClassName();
        $loaded = CoreLoader::classLoader($fullClassName);

        if ($loaded) {
            if ($moduleData->isCallableViewMethod()) {
                $this->updateCount($moduleData->getId());

                $moduleClass = $moduleData->getNewEntityModel();

                // Capture des données d'affichage
                ob_start();
                echo $moduleClass->display($moduleData->getView());
                $moduleData->setTemporyOutputBuffer(ob_get_clean());
            } else {
                CoreLogger::addError(FailBase::getErrorCodeDescription(FailBase::getErrorCodeName(24)) . " (" . $moduleData->getName() . ")");
            }
        } else {
            CoreLogger::addError(FailBase::getErrorCodeDescription(FailBase::getErrorCodeName(24)) . " (" . $moduleData->getName() . ")");
        }
    }

    /**
     * Mise à jour du compteur de visite du module courant.
     *
     * @param int $moduleId
     */
    private function updateCount(int $moduleId): void
    {
        $coreSql = CoreSql::getInstance();

        $coreSql->addQuotedValue("count + 1");
        $coreSql->update(CoreTable::MODULES,
                         array(
                    "count" => "count + 1"
                ),
                         array(
                    "module_id = '" . $moduleId . "'"
        ));
    }
}