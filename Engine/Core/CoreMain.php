<?php

namespace PassionEngine\Engine\Core;

use PassionEngine\Engine\Lib\LibBlock;
use PassionEngine\Engine\Fail\FailBase;
use PassionEngine\Engine\Fail\FailCache;
use PassionEngine\Engine\Fail\FailEngine;
use PassionEngine\Engine\Fail\FailSql;
use PassionEngine\Engine\Lib\LibMakeStyle;
use PassionEngine\Engine\Exec\ExecTimeMarker;
use PassionEngine\Engine\Exec\ExecUtils;

/**
 * Gestionnaire du noyau principal du moteur.
 *
 * @author Sébastien Villemain
 */
class CoreMain
{

    /**
     * Instance principale du moteur.
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
     * Représente le chemin actuel.
     *
     * @var CoreRoute
     */
    private $route = null;

    private function __construct()
    {
        $this->configs = new CoreMainData();
        $this->route = CoreRoute::getNewRoute();
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
    public static function checkInstance(): void
    {
        if (self::$coreMain === null) {
            if (PASSION_ENGINE_DEBUGMODE) {
                ExecTimeMarker::startMeasurement('core');
            }

            self::$coreMain = new CoreMain();
            self::$coreMain->prepare();

            if (PASSION_ENGINE_DEBUGMODE) {
                ExecTimeMarker::stopMeasurement('core');
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
     * Retourne le chemin actuel.
     *
     * @return CoreRoute
     */
    public function getRoute(): CoreRoute
    {
        return $this->route;
    }

    /**
     * Démarrage du moteur.
     */
    public function start(): void
    {
        if (PASSION_ENGINE_DEBUGMODE) {
            ExecTimeMarker::startMeasurement('launcher');
        }

        CoreTranslate::checkInstance();

        // Vérification des bannissements
        $coreSession = CoreSession::getInstance();
        $coreSession->checkBanishment();

        CoreHtml::checkInstance();

        // Configure les informations de page demandées
        $this->route->requestLayout();
        $this->checkMakeStyle();

        if (!PASSION_ENGINE_DEBUGMODE) {
            $this->compressionOpen();
        }

        if ($coreSession->bannedSession()) {
            // Isoloire du bannissement
            $coreSession->displayBanishment();
        } else {
            $this->runJobs();
        }

        ExecTimeMarker::stopMeasurement('main');

        if (PASSION_ENGINE_DEBUGMODE) {
            ExecTimeMarker::stopMeasurement('launcher');
        } else {
            $this->compressionClose();
        }
    }

    /**
     * Recherche de nouveau composant.
     *
     * @return bool Nouveau composant détecté.
     */
    public function newComponentDetected(): bool
    {
        // TODO détection de nouveau module a coder
        return false;
    }

    /**
     * Démarrage du processus d'installation.
     */
    public function install(): void
    {
        // TODO installation a coder
//        $installPath = PASSION_ENGINE_ROOT_DIRECTORY . '/install/index.php';
//        if (is_file($installPath)) {
//            require $installPath;
//        }
    }

    /**
     * Routine d'exécution principal.
     */
    private function runJobs(): void
    {
        // Vérification du type d'affichage
        if ($this->route->isDefaultLayout()) {
            $this->displayDefaultLayout();
        } else {
            // Affichage autonome des modules et blocks
            if ($this->route->isModuleLayout()) {
                $this->displayModuleLayout();
            } else if ($this->route->isBlockLayout()) {
                $this->displayBlockLayout();
            }

            // Execute la commande de récupération d'erreur
            CoreLogger::displayUserMessages();

            // Javascript autonome
            CoreHtml::getInstance()->selfJavascript();
        }

        // Validation et routine du cache
        CoreCache::getInstance()->runJobs();

        if (PASSION_ENGINE_DEBUGMODE) {
            // Assemble tous les messages d'erreurs dans un fichier log
            CoreLogger::logException();
        }
    }

    /**
     * Affichage classique du site.
     */
    private function displayDefaultLayout(): void
    {
        if ($this->getConfigs()->isInMaintenanceMode()) {
            // Mode maintenance: possibilité de s'identifier
            $this->route->setBlockType('Login');
            $this->route->requestBlock();
            $requestedBlockData = $this->route->getRequestedBlockDataByType();
            $requestedBlockData->buildFinalOutput();

            // Affichage des données de la page de maintenance (fermeture)
            $libMakeStyle = new LibMakeStyle();
            $libMakeStyle->assignString('closeText',
                                        CoreTranslate::getConstantDescription(FailBase::getErrorCodeName(8)));
            $libMakeStyle->assignString('closeReason',
                                        $this->getConfigs()->getDefaultSiteCloseReason());
            $libMakeStyle->display('close');
        } else {
            // Mode normal: exécution générale
            $this->route->requestModule();
            $requestedModuleData = $this->route->getRequestedModuleData();
            $requestedModuleData->buildFinalOutput();

            LibBlock::getInstance()->buildAllBlocks();

            $libMakeStyle = new LibMakeStyle();
            $libMakeStyle->display('main');
        }
    }

    /**
     * Affichage du module uniquement.
     */
    private function displayModuleLayout(): void
    {
        $this->route->requestModule();
        $requestedModuleData = $this->route->getRequestedModuleData();
        $requestedModuleData->buildFinalOutput();
        echo $requestedModuleData->getFinalOutput();
    }

    /**
     * Affichage du block uniquement.
     */
    private function displayBlockLayout(): void
    {
        $this->route->requestBlock();
        $requestedBlockData = $this->route->getRequestedBlockDataById();
        $requestedBlockData->buildFinalOutput();
        echo $requestedBlockData->getFinalOutput();
    }

    /**
     * Vérification et assignation du template.
     */
    private function checkMakeStyle(): void
    {
        // Tentative d'utilisation du template du client
        $templateName = CoreSession::getInstance()->getSessionData()->getTemplate();

        if (empty($templateName) || !LibMakeStyle::isTemplateDirectory($templateName)) {
            // Tentative d'utilisation du template du site
            $templateName = $this->getConfigs()->getDefaultTemplate();
        }

        LibMakeStyle::configureTemplateDirectory($templateName);
    }

    /**
     * Enclenche la temporisation de sortie.
     */
    private function compressionOpen(): void
    {
        header('Vary: Cookie, Accept-Encoding');
        $callback = null;

        // HTTP_ACCEPT_ENCODING => gzip
        if (extension_loaded('zlib') && ini_get('zlib.output_compression') !== '1' && function_exists('ob_gzhandler') && !$this->getConfigs()->doUrlRewriting()) {
            $callback = 'ob_gzhandler';
        }

        if (!ob_start($callback)) {
            CoreLogger::addDebug('Unable to turn on ' . $callback . ' output buffering.');
        }
    }

    /**
     * Envoie les données du tampon de sortie et éteint la temporisation de sortie.
     */
    private function compressionClose(): void
    {
        $canContinue = false;

        do {
            $canContinue = ob_end_flush();
        } while ($canContinue);
    }

    /**
     * Préparation du moteur.
     * Procédure de préparation du moteur.
     * Une étape avant le démarrage réel.
     *
     * @throws FailCache
     * @throws FailSql
     * @throws FailEngine
     */
    private function prepare(): void
    {
        if (!$this->loadCache()) {
            throw new FailCache('unable to load cache config',
                                FailBase::getErrorCodeName(5),
                                                           array(CoreLoader::getIncludeAbsolutePath('Includes_cache')));
        }

        if (!$this->loadSql()) {
            throw new FailSql('unable to load database config',
                              FailBase::getErrorCodeName(6),
                                                         array(CoreLoader::getIncludeAbsolutePath('Includes_database')));
        }

        if (!$this->loadConfig()) {
            throw new FailEngine('unable to general config',
                                 FailBase::getErrorCodeName(7),
                                                            array(CoreLoader::getIncludeAbsolutePath('Includes_config')));
        }

        // Chargement de la session
        CoreSession::checkInstance();
    }

    /**
     * Charge le gestionnaire de cache.
     *
     * @return bool Chargé
     */
    private function loadCache(): bool
    {
        $canUse = CoreLoader::isCallable('CoreCache');

        if (!$canUse) {
            // Chemin vers le fichier de configuration du cache
            if (CoreLoader::includeLoader('Includes_cache')) {
                // Démarrage de l'instance CoreCache
                CoreCache::checkInstance();

                $canUse = true;
            }
        }
        return $canUse;
    }

    /**
     * Charge le gestionnaire SQL.
     *
     * @return bool Chargé
     */
    private function loadSql(): bool
    {
        $canUse = CoreLoader::isCallable('CoreSql');

        if (!$canUse) {
            // Chemin vers le fichier de configuration de la base de données
            if (CoreLoader::includeLoader('Includes_database')) {
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
     * @throws FailEngine
     */
    private function loadConfig(): bool
    {
        // Chemin vers le fichier de configuration du moteur
        $canUse = CoreLoader::includeLoader('Includes_config');

        if ($canUse) {
            $canUse = $this->getConfigs()->initialize();

            if (defined('PASSION_ENGINE_STATUT') && PASSION_ENGINE_STATUT == 'close') {
                throw new FailEngine('web site is closed',
                                     FailBase::getErrorCodeName(8));
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
    private function loadGenericConfig(): void
    {
        $configs = array();
        $coreCache = CoreCache::getInstance(CoreCacheSection::TMP);

        // Si le cache est disponible
        if ($coreCache->cached('configs.php')) {
            // Chargement de la configuration via la cache
            $configs = $coreCache->readCacheAsArray('configs.php');
        } else {
            $content = '';
            $coreSql = CoreSql::getInstance()->getSelectedBase();

            // Requête vers la base de données de configs
            $coreSql->select(CoreTable::CONFIG,
                             array('name', 'value'))->query();
            $configs = ExecUtils::getArrayConfigs($coreSql->fetchArray());

            foreach ($configs as $key => $value) {
                $content .= $coreCache->serializeData(array($key => $value));
            }

            // Mise en cache
            $coreCache->writeCacheAsString('configs.php',
                                           $content);
        }

        // Ajout a la configuration courante
        $this->getConfigs()->addConfig($configs);
    }
}