<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Lib\LibBlock;
use TREngine\Engine\Fail\FailBase;
use TREngine\Engine\Fail\FailCache;
use TREngine\Engine\Fail\FailEngine;
use TREngine\Engine\Fail\FailSql;
use TREngine\Engine\Lib\LibModule;
use TREngine\Engine\Lib\LibMakeStyle;
use TREngine\Engine\Exec\ExecTimeMarker;
use TREngine\Engine\Exec\ExecUtils;

/**
 * Gestionnaire du noyau et d'enchainement dans le moteur.
 * Classe principal du moteur.
 *
 * @author Sébastien Villemain
 */
class CoreMain
{

    /**
     * Instance principal du moteur.
     *
     * @var CoreMain
     */
    private static $coreMain = null;

    /**
     * Information sur la configuration.
     *
     * @var CoreMainData
     */
    private $configs = null;

    /**
     * Informations sur l'agent.
     *
     * @var CoreUserAgentData
     */
    private $agentInfos = null;

    /**
     * Mode de mise en page courante.
     * default : affichage normale et complet
     * module : affichage uniquement du module si javascript activé
     * block : affichage uniquement du block si javascript activé
     * modulepage : affichage uniquement du module forcé
     * blockpage : affichage uniquement du block forcé
     *
     * @var string
     */
    private $layout = "default";

    private function __construct()
    {
        $this->configs = new CoreMainData();
    }

    /**
     * Retourne l'instance principal du moteur.
     *
     * @return CoreMain
     */
    public static function &getInstance(): CoreMain
    {
        self::checkInstance();
        return self::$coreMain;
    }

    /**
     * Vérification de l'instance principal du moteur.
     */
    public static function checkInstance()
    {
        if (self::$coreMain === null) {
            if (CoreSecure::debuggingMode()) {
                ExecTimeMarker::startMeasurement("core");
            }

            self::$coreMain = new CoreMain();
            self::$coreMain->prepare();

            if (CoreSecure::debuggingMode()) {
                ExecTimeMarker::stopMeasurement("core");
            }
        }
    }

    /**
     * Retourne les données de l'agent actuel.
     *
     * @return CoreUserAgentData
     */
    public function getAgentInfos(): CoreUserAgentData
    {
        if ($this->agentInfos === null) {
            $this->agentInfos = new CoreUserAgentData();
        }
        return $this->agentInfos;
    }

    /**
     * Retourne les informations sur la configuration.
     *
     * @return CoreMainData
     */
    public function getConfigs(): CoreMainData
    {
        return $this->configs;
    }

    /**
     * Détermine si l'affichage se fait en écran complet (affichage classique).
     *
     * @return bool true c'est en plein écran.
     */
    public function isDefaultLayout(): bool
    {
        return (($this->layout === "default") ? true : false);
    }

    /**
     * Détermine si l'affichage se fait en écran minimal ciblé module.
     *
     * @return bool true c'est un affichage de module uniquement.
     */
    public function isModuleLayout(): bool
    {
        return (($this->layout === "module" || $this->layout === "modulepage") ? true : false);
    }

    /**
     * Détermine si l'affichage se fait en écran minimal ciblé block.
     *
     * @return bool true c'est un affichage de block uniquement.
     */
    public function isBlockLayout(): bool
    {
        return (($this->layout === "block" || $this->layout == "blockpage") ? true : false);
    }

    /**
     * Démarrage TR ENGINE.
     */
    public function start()
    {
        if (CoreSecure::debuggingMode()) {
            ExecTimeMarker::startMeasurement("launcher");
        }

        CoreTranslate::checkInstance();

        // Vérification des bannissements
        $coreSession = CoreSession::getInstance();
        $coreSession->checkBanishment();

        CoreHtml::checkInstance();

        // Configure les informations de page demandées
        $this->checkLayout();
        $this->checkMakeStyle();

        if (!CoreSecure::debuggingMode()) {
            $this->compressionOpen();
        }

        if ($coreSession->bannedSession()) {
            // Isoloire du bannissement
            $coreSession->displayBanishment();
        } else {
            $this->runJobs();
        }

        ExecTimeMarker::stopMeasurement("main");

        if (CoreSecure::debuggingMode()) {
            ExecTimeMarker::stopMeasurement("launcher");
        } else {
            $this->compressionClose();
        }
    }

    /**
     * Recherche de nouveau composant.
     *
     * @return bool true nouveau composant détecté.
     */
    public function newComponentDetected(): bool
    {
        // TODO détection de nouveau module a coder
        return false;
    }

    /**
     * Démarrage de l'installeur.
     */
    public function install()
    {
        // TODO installation a coder
//        $installPath = TR_ENGINE_INDEX_DIRECTORY . "/install/index.php";
//        if (is_file($installPath)) {
//            require $installPath;
//        }
    }

    /**
     * Routine d'exécution principal.
     */
    private function runJobs()
    {
        // Vérification du type d'affichage
        if ($this->isDefaultLayout()) {
            $this->displayDefaultLayout();
        } else {
            // Affichage autonome des modules et blocks
            if ($this->isModuleLayout()) {
                $this->displayModuleLayout();
            } else if ($this->isBlockLayout()) {
                $this->displayBlockLayout();
            }

            // Execute la commande de récupération d'erreur
            CoreLogger::displayMessages();

            // Javascript autonome
            CoreHtml::getInstance()->selfJavascript();
        }

        // Validation et routine du cache
        CoreCache::getInstance()->runJobs();

        if (CoreSecure::debuggingMode()) {
            // Assemble tous les messages d'erreurs dans un fichier log
            CoreLogger::logException();
        }
    }

    /**
     * Affichage classique du site.
     */
    private function displayDefaultLayout()
    {
        if ($this->getConfigs()->doDumb()) {
            // Mode maintenance: possibilité de s'identifier
            LibBlock::getInstance()->buildStandaloneBlockType("Login");

            // Affichage des données de la page de maintenance (fermeture)
            $libMakeStyle = new LibMakeStyle();
            $libMakeStyle->assignString("closeText",
                                        FailBase::getErrorCodeDescription(8));
            $libMakeStyle->assignString("closeReason",
                                        $this->getConfigs()->getDefaultSiteCloseReason());
            $libMakeStyle->display("close");
        } else {
            // Mode normal: exécution général
            LibModule::getInstance()->buildRequestedModule();
            LibBlock::getInstance()->buildAllBlocks();

            $libMakeStyle = new LibMakeStyle();
            $libMakeStyle->display("main");
        }
    }

    /**
     * Affichage du module uniquement.
     */
    private function displayModuleLayout()
    {
        $libModule = LibModule::getInstance();
        $libModule->buildRequestedModule();

        echo $libModule->getBuildedModule();
    }

    /**
     * Affichage du block uniquement.
     */
    private function displayBlockLayout()
    {
        $libBlock = LibBlock::getInstance();
        $libBlock->buildBlockRequested();

        echo $libBlock->getFirstBlockBuilded();
    }

    /**
     * Vérification et assignation du layout.
     */
    private function checkLayout()
    {
        // Assignation et vérification de fonction layout
        $layout = strtolower(CoreRequest::getWord("layout"));

        // Configuration du layout
        if ($layout !== "default" && $layout !== "modulepage" && $layout !== "blockpage" && (($layout !== "block" && $layout !== "module") || (!CoreHtml::getInstance()->javascriptEnabled()))) {
            $layout = "default";
        }

        $this->layout = $layout;
    }

    /**
     * Vérification et assignation du template.
     */
    private function checkMakeStyle()
    {
        // Tentative d'utilisation du template du client
        $templateName = CoreSession::getInstance()->getUserInfos()->getTemplate();

        if (empty($templateName) || !LibMakeStyle::isTemplateDirectory($templateName)) {
            // Tentative d'utilisation du template du site
            $templateName = $this->getConfigs()->getDefaultTemplate();
        }

        LibMakeStyle::configureTemplateDirectory($templateName);
    }

    /**
     * Lance le tampon de sortie.
     * Entête & tamporisation de sortie.
     */
    private function compressionOpen()
    {
        header("Vary: Cookie, Accept-Encoding");
        // HTTP_ACCEPT_ENCODING => gzip
        if (extension_loaded('zlib') && ini_get('zlib.output_compression') !== "1" && function_exists("ob_gzhandler") && !$this->getConfigs()->doUrlRewriting()) {
            ob_start("ob_gzhandler");
        } else {
            ob_start();
        }
    }

    /**
     * Relachement des tampons de sortie.
     */
    private function compressionClose()
    {
        $canContinue = false;

        do {
            $canContinue = ob_end_flush();
        } while ($canContinue);
    }

    /**
     * Préparation TR ENGINE.
     * Procédure de préparation du moteur.
     * Une étape avant le démarrage réel.
     */
    private function prepare()
    {
        if (!$this->loadCache()) {
            CoreSecure::getInstance()->catchException(new FailCache("unable to load cache config",
                                                                    5,
                                                                    array(CoreLoader::getIncludeAbsolutePath("configs_cache"))));
        }

        if (!$this->loadSql()) {
            CoreSecure::getInstance()->catchException(new FailSql("unable to load database config",
                                                                  6,
                                                                  array(CoreLoader::getIncludeAbsolutePath("configs_database"))));
        }

        if (!$this->loadConfig()) {
            CoreSecure::getInstance()->catchException(new FailEngine("unable to general config",
                                                                     7,
                                                                     array(CoreLoader::getIncludeAbsolutePath("configs_config"))));
        }

        // Chargement de la session
        CoreSession::checkInstance();
    }

    /**
     * Charge le gestionnaire de cache.
     *
     * @return bool true chargé
     */
    private function loadCache(): bool
    {
        $canUse = CoreLoader::isCallable("CoreCache");

        if (!$canUse) {
            // Chemin vers le fichier de configuration du cache
            if (CoreLoader::includeLoader("configs_cache")) {
                // Démarrage de l'instance CoreCache
                CoreCache::checkInstance();

                $canUse = true;
            }
        }
        return $canUse;
    }

    /**
     * Charge le gestionnaire Sql.
     *
     * @return bool true chargé
     */
    private function loadSql(): bool
    {
        $canUse = CoreLoader::isCallable("CoreSql");

        if (!$canUse) {
            // Chemin vers le fichier de configuration de la base de données
            if (CoreLoader::includeLoader("configs_database")) {
                // Démarrage de l'instance CoreSql
                CoreSql::checkInstance();

                $canUse = true;
            }
        }
        return $canUse;
    }

    /**
     * Charge la configuration générale.
     *
     * @return bool
     */
    private function loadConfig(): bool
    {
        // Chemin vers le fichier de configuration du moteur
        $canUse = CoreLoader::includeLoader("configs_config");

        if ($canUse) {
            $canUse = $this->getConfigs()->initialize();

            if (defined("TR_ENGINE_STATUT") && TR_ENGINE_STATUT == "close") {
                CoreSecure::getInstance()->catchException(new FailEngine("web site is closed",
                                                                         8));
            }

            // Si tout semble en ordre, nous continuons le chargement
            if ($canUse) {
                $this->loadGenericConfig();
            }
        }
        return $canUse;
    }

    /**
     * Chargement de la configuration générique (via table de données).
     */
    private function loadGenericConfig()
    {
        $configs = array();
        $coreCache = CoreCache::getInstance(CoreCacheSection::TMP);

        // Si le cache est disponible
        if ($coreCache->cached("configs.php")) {
            // Chargement de la configuration via la cache
            $configs = $coreCache->readCacheAsArray("configs.php");
        } else {
            $content = "";
            $coreSql = CoreSql::getInstance();

            // Requête vers la base de données de configs
            $coreSql->select(CoreTable::CONFIG,
                             array("name", "value"));
            $configs = ExecUtils::getArrayConfigs($coreSql->fetchArray());

            foreach ($configs as $key => $value) {
                $content .= $coreCache->serializeData(array($key => $value));
            }

            // Mise en cache
            $coreCache->writeCache("configs.php",
                                   $content);
        }

        // Ajout a la configuration courante
        $this->getConfigs()->addConfig($configs);
    }
}