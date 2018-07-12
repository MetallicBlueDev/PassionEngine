<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreAccess;
use TREngine\Engine\Core\CoreCacheSection;
use TREngine\Engine\Core\CoreAccessType;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Core\CoreMain;
use TREngine\Engine\Core\CoreRequest;
use TREngine\Engine\Core\CoreSecure;
use TREngine\Engine\Core\CoreSession;
use TREngine\Engine\Core\CoreSql;
use TREngine\Engine\Core\CoreTable;
use TREngine\Engine\Core\CoreTranslate;
use TREngine\Engine\Core\CoreUrlRewriting;
use TREngine\Engine\Exec\ExecUtils;
use Exception;

/**
 * Gestionnaire de module.
 *
 * @author Sébastien Villemain
 */
class LibModule
{

    /**
     * Nom de la page par défaut.
     *
     * @var string
     */
    private const DEFAULT_PAGE = "index";

    /**
     * Nom de la méthode d'affichage par défaut.
     *
     * @var string
     */
    private const DEFAULT_VIEW = "display";

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
     * Nom du module courant.
     *
     * @var string
     */
    private $module = "";

    /**
     * Tableau d'information sur les modules.
     *
     * @var array
     */
    private $modulesInfo = array();

    /**
     * Création du gestionnaire.
     */
    private function __construct()
    {

    }

    /**
     * Vérification de l'instance du gestionnaire des modules.
     */
    public static function checkInstance()
    {
        if (self::$libModule === null) {
            // Création d'un instance autonome
            self::$libModule = new LibModule();

            $defaultModule = CoreMain::getInstance()->getConfigs()->getDefaultModule();
            $module = self::getRequestedModule($defaultModule);
            $page = self::getRequestedPage();
            $moduleData = self::getRequestedModuleData($module,
                                                       $page);

            if ($moduleData == null && $module !== $defaultModule) {
                // Afficher une erreur 404
                if (!empty($module) || !empty($page)) {
                    CoreLogger::addInfo(ERROR_404);
                }

                // Utilisation du module par défaut
                $moduleData = self::$libModule->getModuleData($defaultModule);
                $moduleData->setPage(self::DEFAULT_PAGE);
                $moduleData->setView(self::DEFAULT_VIEW);
            }

            self::$libModule->module = $moduleData->getName();
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
     * Détermine si le module est en cours d'utilisation.
     *
     * @param string $moduleName
     * @return bool true le module est actuellement sélectionné
     */
    public static function &isSelectedModule(string $moduleName): bool
    {
        $selected = false;

        if (self::$libModule !== null) {
            $selected = self::$libModule->module === $moduleName;
        }
        return $selected;
    }

    /**
     * Retourne les informations du module cible.
     *
     * @return LibModuleData Informations sur le module.
     */
    public function &getSelectedModuleData(): LibModuleData
    {
        return $this->getModuleData($this->module);
    }

    /**
     * Retourne les informations du module cible.
     *
     * @param string $moduleName Le nom du module, par défaut le module courant.
     * @return LibModuleData Informations sur le module.
     */
    public function &getModuleData(string $moduleName): LibModuleData
    {
        $moduleInfo = null;

        if (!empty($moduleName)) {
            $moduleName = ucfirst($moduleName);

            if (isset($this->modulesInfo[$moduleName])) {
                $moduleInfo = $this->modulesInfo[$moduleName];
            } else {
                $moduleInfo = $this->requestModuleData($moduleName);
            }
        }
        return $moduleInfo;
    }

    /**
     * Démarrage du module courant.
     */
    public function launch()
    {
        $moduleInfo = $this->getSelectedModuleData();

        // Vérification du niveau d'acces
        if (($moduleInfo->installed() && CoreAccess::autorize(CoreAccessType::getTypeFromToken($moduleInfo))) || (!$moduleInfo->installed() && CoreSession::getInstance()->getUserInfos()->hasAdminRank())) {
            if ($moduleInfo->isValid()) {
                CoreTranslate::getInstance()->translate($moduleInfo->getFolderName());

                $libBreadcrumb = LibBreadcrumb::getInstance();
                $libBreadcrumb->addTrail($moduleInfo->getName(),
                                         "?module=" . $moduleInfo->getName());

                // TODO A MODIFIER
                // Juste une petite exception pour le module management qui est different
                if ($moduleInfo->getName() !== "management") {
                    $libBreadcrumb->addTrail($moduleInfo->getView(),
                                             "?module=" . $moduleInfo->getName() . "&view=" . $moduleInfo->getView());
                }

                $this->get($moduleInfo);
            }
        } else {
            CoreLogger::addError(ERROR_ACCES_ZONE . " " . CoreAccess::getAccessErrorMessage($moduleInfo));
        }
    }

    /**
     * Retourne le module compilé.
     *
     * @return string
     */
    public function &getModule(): string
    {
        $selectedModuleData = $this->getSelectedModuleData();
        $buffer = $selectedModuleData->getBuffer();
        $configs = $selectedModuleData->getConfigs();

        // Recherche le parametre indiquant qu'il doit y avoir une réécriture du buffer
        if ($configs !== null && ExecUtils::inArray("rewriteBuffer",
                                                    $configs,
                                                    false)) {
            $buffer = CoreUrlRewriting::getInstance()->rewriteBuffer($buffer);
        }
        return $buffer;
    }

    /**
     * Demande le chargement des informations du module.
     *
     * @param string $moduleName Le nom du module.
     * @return LibModuleData Informations sur le module.
     */
    private function &requestModuleData(string $moduleName): LibModuleData
    {
        $moduleInfo = null;
        $dbRequest = false;
        $moduleData = array();

        // Recherche dans le cache
        $coreCache = CoreCache::getInstance(CoreCacheSection::MODULES);

        if (!$coreCache->cached($moduleName . ".php")) {
            $moduleData = $this->loadModuleData($moduleName);
            $dbRequest = !empty($moduleData);
        } else {
            $moduleData = $coreCache->readCacheAsArray($moduleName . ".php");
        }

        // Injection des informations du module
        $moduleInfo = new LibModuleData($moduleData,
                                        $dbRequest);
        $this->modulesInfo[$moduleName] = $moduleInfo;

        if ($dbRequest) {
            // Mise en cache
            $content = $coreCache->serializeData($moduleData);
            $coreCache->writeCache($moduleName . ".php",
                                   $content);
        }
        return $moduleInfo;
    }

    /**
     * Chargement des informations du module.
     *
     * @param string $moduleName Le nom du module.
     * @return array Informations sur le module.
     */
    private function &loadModuleData(string $moduleName): array
    {
        $moduleData = array();
        $coreSql = CoreSql::getInstance();
        $coreSql->select(CoreTable::MODULES,
                         array("mod_id", "name", "rank"),
                         array("name =  '" . $moduleName . "'"));

        if ($coreSql->affectedRows() > 0) {
            $moduleData = $coreSql->fetchArray()[0];
            $moduleData['modConfigs'] = array();

            $coreSql->select(CoreTable::MODULES_CONFIGS,
                             array("name", "value"),
                             array("mod_id =  '" . $moduleData['mod_id'] . "'"
            ));

            if ($coreSql->affectedRows() > 0) {
                $moduleData['modConfigs'] = $coreSql->fetchArray();
            }
        }
        return $moduleData;
    }

    /**
     * Récupère le module.
     *
     * @param LibModuleData $moduleInfo
     */
    private function get(LibModuleData &$moduleInfo)
    {
        $moduleClassName = CoreLoader::getFullQualifiedClassName($moduleInfo->getClassName(),
                                                                 $moduleInfo->getFolderName());
        $loaded = CoreLoader::classLoader($moduleClassName);

        // Vérification de la sous page
        $moduleInfo->setView($this->getValidViewPage(array($moduleClassName,
                ($moduleInfo->installed()) ? $moduleInfo->getView() : "install")));

        // Affichage du module si possible
        if ($loaded && !empty($moduleInfo->getView())) {
            $this->updateCount($moduleInfo->getId());

            try {
                /**
                 * @var ModuleModel
                 */
                $moduleClass = new $moduleClassName();
                $moduleClass->setModuleData($moduleInfo);

                // Capture des données d'affichage
                ob_start();
                echo $moduleClass->{$moduleInfo->getView()}();
                $moduleInfo->setBuffer(ob_get_clean());
            } catch (Exception $ex) {
                CoreSecure::getInstance()->catchException($ex);
            }
        } else {
            CoreLogger::addError(ERROR_MODULE_CODE . " (" . $moduleInfo->getName() . ")");
        }
    }

    /**
     * Retourne un view valide sinon une chaine vide.
     *
     * @param array $pageInfo
     * @return string
     */
    private function &getValidViewPage(array $pageInfo): string
    {
        $invalid = false;

        if (CoreLoader::isCallable($pageInfo[0],
                                   $pageInfo[1])) {
            $userInfos = CoreSession::getInstance()->getUserInfos();
            $selectedModuleData = $this->getSelectedModuleData();

            if ($pageInfo[1] === "install" && ($selectedModuleData->installed() || !$userInfos->hasAdminRank())) {
                $invalid = true;
            } else if ($pageInfo[1] === "uninstall" && (!$selectedModuleData->installed() || !$userInfos->hasAdminRank())) {
                $invalid = true;
            } else if ($pageInfo[1] === "setting" && (!$selectedModuleData->installed() || !$userInfos->hasAdminRank())) {
                $invalid = true;
            }
        } else {
            $invalid = true;
        }

        $rslt = "";

        if ($invalid) {
            $default = "display";

            if ($pageInfo[1] !== $default) {
                $rslt = $this->getValidViewPage(array(
                    $pageInfo[0],
                    $default
                ));
            }
        } else {
            $rslt = $pageInfo[1];
        }
        return $rslt;
    }

    /**
     * Mise à jour du compteur de visite du module courant.
     *
     * @param int $modId
     */
    private function updateCount(int $modId)
    {
        $coreSql = CoreSql::getInstance();

        $coreSql->addQuotedValue("count + 1");
        $coreSql->update(CoreTable::MODULES,
                         array(
                "count" => "count + 1"
            ),
                         array(
                "mod_id = '" . $modId . "'"
        ));
    }

    /**
     * Retourne le module demandée.
     *
     * @param string $defaultModule Le module par défaut.
     * @return string
     */
    private static function getRequestedModule(string $defaultModule): string
    {
        $module = CoreRequest::getWord("module");

        if (empty($module)) {
            $module = $defaultModule;
        }
        return $module;
    }

    /**
     * Retourne la page demandée.
     *
     * @return string
     */
    private static function getRequestedPage(): string
    {
        $page = CoreRequest::getWord("page");

        if (empty($page)) {
            $page = self::DEFAULT_PAGE;
        }
        return $page;
    }

    /**
     * Retourne la méthode d'affichage.
     *
     * @return string
     */
    private static function &getRequestedView(): string
    {
        $view = CoreRequest::getWord("view");

        if (empty($view)) {
            $view = self::DEFAULT_VIEW;
        }
        return $view;
    }

    /**
     * Retourne les informations sur le module demandé.
     *
     * @param string $module
     * @param string $page
     * @return LibModuleData
     */
    private static function getRequestedModuleData(string $module,
                                                   string $page): LibModuleData
    {
        $moduleData = self::$libModule->getModuleData($module);

        if ($moduleData != null) {
            $view = self::getRequestedView();

            $moduleData->setPage($page);
            $moduleData->setView($view);

            if (!$moduleData->isValid()) {
                $moduleData = null;
            }
        }
        return $moduleData;
    }
}