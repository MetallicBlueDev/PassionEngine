<?php

namespace TREngine\Engine\Core;

use TREngine\Engine\Lib\LibMakeStyle;
use TREngine\Engine\Lib\LibModule;
use TREngine\Engine\Exec\ExecString;
use TREngine\Engine\Exec\ExecCookie;
use TREngine\Engine\Exec\ExecCrypt;

/**
 * Gestionnaire d'entête et de contenu HTML.
 *
 * @author Sébastien Villemain
 */
class CoreHtml
{

    /**
     * Instance de cette classe.
     *
     * @var CoreHtml
     */
    private static $coreHtml = null;

    /**
     * Nom du cookie de test.
     *
     * @var string
     */
    private $cookieTestName = "test";

    /**
     * Etat du javaScript chez le client.
     *
     * @var bool
     */
    private $javaScriptEnabled = false;

    /**
     * Fonctions et codes javascript demandées.
     *
     * @var string
     */
    private $javaScriptCode = "";

    /**
     * Fonctions et codes javascript JQUERY demandées.
     *
     * @var string
     */
    private $javaScriptJquery = "";

    /**
     * Fichiers de javascript demandées.
     *
     * @var array
     */
    private $javaScriptFile = array();

    /**
     * Fichier de style CSS demandées.
     *
     * @var array
     */
    private $cssFile = array();

    /**
     * Titre de la page courante.
     *
     * @var string
     */
    private $title = "";

    /**
     * Mots clès de la page courante.
     *
     * @var array
     */
    private $keywords = array();

    /**
     * Description de la page courante.
     *
     * @var string
     */
    private $description = "";

    /**
     * Nouveau gestionnaire.
     */
    private function __construct()
    {
        // Configuration du préfixe accessible
        if (CoreLoader::isCallable("CoreMain")) {
            $prefix = CoreMain::getInstance()->getConfigs()->getCookiePrefix();
        } else {
            $prefix = "tr";
        }

        // Composition du nom du cookie de test
        $this->cookieTestName = ExecCrypt::cryptByStandard(
                $prefix . "_" . $this->cookieTestName,
                self::getSalt()
        );

        // Vérification du javascript du client
        $this->checkJavascriptEnabled();
    }

    /**
     * Retourne et si besoin créé l'instance CoreHtml.
     *
     * @return CoreHtml
     */
    public static function &getInstance(): CoreHtml
    {
        self::checkInstance();
        return self::$coreHtml;
    }

    /**
     * Vérification de l'instance du gestionnaire de HTML.
     */
    public static function checkInstance()
    {
        if (self::$coreHtml === null) {
            self::$coreHtml = new CoreHtml();
        }
    }

    /**
     * Ajoute un code javaScript JQUERY à exécuter.
     *
     * @param string $javaScript
     */
    public function addJavascriptJquery(string $javaScript)
    {
        if (!empty($this->javaScriptJquery)) {
            $this->javaScriptJquery .= "\n";
        }

        $this->javaScriptJquery .= $javaScript;
    }

    /**
     * Ajoute un code javaScript pur à exécuter.
     *
     * @param string $javaScript
     */
    public function addJavascriptCode(string $javaScript)
    {
        if (!empty($this->javaScriptCode)) {
            $this->javaScriptCode .= "\n";
        }

        $this->javaScriptCode .= $javaScript;
    }

    /**
     * Ajoute un code javascript (compatible Jquery et javascript pur) à exécuter.
     *
     * @param string $javaScript
     */
    public function addJavascript(string $javaScript)
    {
        if ($this->javascriptEnabled() && CoreLoader::isCallable("CoreMain") && CoreMain::getInstance()->isDefaultLayout()) {
            $this->addJavascriptJquery($javaScript);
        } else {
            $this->addJavascriptCode($javaScript);
        }
    }

    /**
     * Retourne l'état du javascript du client.
     *
     * @return bool
     */
    public function &javascriptEnabled(): bool
    {
        return $this->javaScriptEnabled;
    }

    /**
     * Ajoute un fichier javascript à l'entête.
     *
     * @param string $fileName
     * @param string $options
     */
    public function addJavascriptFile(string $fileName,
                                      string $options = "")
    {
        if (!array_key_exists($fileName,
                              $this->javaScriptFile)) {
            if ($fileName === "jquery.js") {
                // Fixe JQuery en 1ere position
                $this->javaScriptFile = array_merge(array(
                    $fileName => $options),
                                                    $this->javaScriptFile);
            } else if ($fileName === "tr_engine.js") {
                // Fixe tr_engine en 2em position
                if (array_key_exists("jquery.js",
                                     $this->javaScriptFile)) {
                    $this->javaScriptFile = array_merge(array(
                        "jquery.js" => $this->javaScriptFile['jquery.js'],
                        $fileName => $options),
                                                        $this->javaScriptFile);
                } else {
                    $this->javaScriptFile = array_merge(array(
                        $fileName => $options),
                                                        $this->javaScriptFile);
                }
            } else {
                $this->javaScriptFile[$fileName] = $options;
            }
        }
    }

    /**
     * Ajoute une feuille de style CSS provenant du dossier ressource/css à l'entête.
     *
     * @param string $fileName
     * @param string $options
     */
    public function addCssResourceFile(string $fileName,
                                       string $options = "")
    {
        $this->addCssFile("Resources" . DIRECTORY_SEPARATOR . "Css" . DIRECTORY_SEPARATOR . $fileName,
                          $options);
    }

    /**
     * Ajoute un fichier de style .CSS provenant du dossier template à l'entête.
     *
     * @param string $fileName
     * @param string $options
     */
    public function addCssTemplateFile(string $fileName,
                                       string $options = "")
    {
        if (CoreLoader::isCallable("LibMakeStyle")) {
            $this->addCssFile(LibMakeStyle::getTemplateDirectory() . DIRECTORY_SEPARATOR . $fileName,
                              $options);
        }
    }

    /**
     * Retourne le titre à inclure dans les métas données de l'entête HTML.
     *
     * @return string
     */
    public function getMetaTitle(): string
    {
        $title = "";

        if (CoreLoader::isCallable("CoreMain")) {
            $coreMain = CoreMain::getInstance();
            $title = $coreMain->getConfigs()->getDefaultSiteName();

            if (empty($this->title)) {
                // Titre automatique
                $title .= " - " . $coreMain->getConfigs()->getDefaultSiteSlogan();

                if (CoreLoader::isCallable("LibModule")) {
                    $title .= " / " . LibModule::getInstance()->getRequestedModuleData()->getName();
                }
            } else {
                // Titre manuel
                $title = $this->title . " - " . $title;
            }
        } else {
            // Titre en mode dégradé
            $title .= CoreRequest::getString("SERVER_NAME",
                                             "",
                                             CoreRequestType::SERVER);

            if (!empty($this->title)) {
                $title .= " - " . $this->title;
            }
        }
        return ExecString::textDisplay($title);
    }

    /**
     * Retourne les métas données de l'entête HTML.
     *
     * @return string
     */
    public function getMetaHeaders(): string
    {
        // TODO ajouter un support RSS XML
        return $this->getMetaKeywords()
            . "<meta name=\"generator\" content=\"TR ENGINE\" />\n"
            . "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />\n"
            . "<meta http-equiv=\"content-script-type\" content=\"text/javascript\" />\n"
            . "<meta http-equiv=\"content-style-type\" content=\"text/css\" />\n"
            . "<link rel=\"shortcut icon\" type=\"image/x-icon\" href=\"" . LibMakeStyle::getTemplateDirectory() . "/favicon.ico\" />\n"
            . $this->getMetaIncludeJavascript()
            . $this->getMetaIncludeCss();
    }

    /**
     * Retourne les métas données de bas de page HTML.
     *
     * @return string
     */
    public function getMetaFooters(): string
    {
        // TODO continuer le footer
        return $this->getMetaExecuteJavascript();
    }

    /**
     * Affecte le titre à la page courante.
     *
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = strip_tags($title);
    }

    /**
     * Affecte les mots clès de la page courante.
     *
     * @param array $keywords un tableau de mots clés
     */
    public function setKeywords(array $keywords)
    {
        if (empty($this->keywords)) {
            array_push($this->keywords,
                       $keywords);
        } else {
            $this->keywords = $keywords;
        }
    }

    /**
     * Affecte la description de la page courante.
     *
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = strip_tags($description);
    }

    /**
     * Retourne un lien cliquable sans javascript.
     *
     * @param string $link Adresse URL de base.
     * @param string $displayContent Données à afficher (texte simple ou code html)
     * @param bool $layout true ajouter le layout.
     * @param string $onclick Données à exécuter lors du clique
     * @param string $addons Code additionnel
     * @return string
     */
    public static function &getLink(string $link,
                                    string $displayContent = "",
                                    bool $layout = false,
                                    string $onclick = "",
                                    string $addons = ""): string
    {
        $htmlLink = "<a href=\"" . CoreUrlRewriting::getLink($link,
                                                             $layout) . "\"";

        // TODO A vérifier /^[A-Za-z0-9.-\s]+$/ie
        if (preg_match("/^[A-Za-z0-9.\s]+$/ie",
                       $displayContent)) {
            $htmlLink .= " title=\"" . $displayContent . "\"";
        }

        if (!empty($onclick)) {
            $htmlLink .= " onclick=\"" . $onclick . "\"";
        }

        if (!empty($addons)) {
            $htmlLink .= " " . $addons;
        }

        $htmlLink .= ">";

        if (!empty($displayContent)) {
            $htmlLink .= $displayContent;
        }

        $htmlLink .= "</a>";
        return $htmlLink;
    }

    /**
     * Retourne un lien cliquable utilisable avec et sans javascript.
     *
     * @param string $linkWithoutJavascript
     * @param string $linkForJavascript
     * @param string $divId
     * @param string $displayContent
     * @param string $addons Code additionnel
     * @return string
     */
    public static function &getLinkWithAjax(string $linkWithoutJavascript,
                                            string $linkForJavascript,
                                            string $divId,
                                            string $displayContent,
                                            string $addons = ""): string
    {
        // TODO A vérifier
        self::getInstance()->addJavascriptFile("jquery.js");
        return self::getLink($linkWithoutJavascript,
                             $displayContent,
                             false,
                             "validLink('" . $divId . "', '" . CoreUrlRewriting::getLink($linkForJavascript,
                                                                                         true) . "');return false;",
                                                                                         $addons);
    }

    /**
     * Redirection ou chargement via javascript vers une page.
     *
     * @param string $url La page demandée a chargé.
     * @param int $tps Temps avant le chargement de la page.
     * @param string $method Block de destination si ce n'est pas toute la page.
     */
    public function redirect(string $url = "",
                             int $tps = 0,
                             string $method = "window")
    {
        // Configuration du temps
        $tps = ((!is_numeric($tps)) ? 0 : $tps) * 1000;

        // Configuration de l'url
        if (empty($url) || $url === "index.php?") {
            $url = "index.php";
        }

        // Redirection
        if ($this->javascriptEnabled() && ($tps > 0 || $method !== "windows")) {
            if (CoreRequest::getRequestMethod() === CoreRequestType::POST && $method !== "window") {
                // Commande ajax pour la redirection
                $this->addJavascriptCode("setTimeout(function(){ $('" . $method . "').load('" . $url . "'); }, $tps);");
            } else {
                // Commande par défaut
                $this->addJavascriptCode("setTimeout('window.location = \"" . $url . "\"','" . $tps . "');");
            }
        } else {
            // Redirection php sans timeout
            header("Location: " . $url);
        }
    }

    /**
     * Inclus et exécute le javascript de facon autonome.
     */
    public function selfJavascript()
    {
        echo $this->getMetaIncludeJavascript(true) . $this->getMetaExecuteJavascript();
    }

    /**
     * Retourne l'icône de chargement animée.
     *
     * @return string
     */
    public function &getLoader(): string
    {
        $rslt = "";

        if ($this->javascriptEnabled()) {
            $libMakeStyle = new LibMakeStyle("loader");
            $rslt = "<div id=\"loader\">" . $libMakeStyle->render() . "</div>";
        }
        return $rslt;
    }

    /**
     * Retourne la combinaison de clés pour le salt.
     *
     * @return string
     */
    private function &getSalt(): string
    {
        // Configuration de la clès si accessible
        if (CoreLoader::isCallable("CoreMain")) {
            $key = CoreMain::getInstance()->getConfigs()->getCryptKey();
        } else {
            $key = "A4bT9D4V";
        }
        return $key;
    }

    /**
     * Détection du javascript chez le client.
     */
    private function checkJavascriptEnabled()
    {
        // Récuperation du cookie en php
        $cookieTest = ExecCookie::getCookie($this->cookieTestName);

        // Vérification de l'existance du cookie
        $this->javaScriptEnabled = ($cookieTest === "1") ? true : false;
    }

    /**
     * Ajoute un fichier de style .CSS à l'entête.
     *
     * @param string $filePath
     * @param string $options
     */
    private function addCssFile(string $filePath,
                                string $options = "")
    {
        if (is_file(TR_ENGINE_INDEX_DIRECTORY . DIRECTORY_SEPARATOR . str_replace("/",
                                                                                  DIRECTORY_SEPARATOR,
                                                                                  $filePath))) {
            $filePath = str_replace(DIRECTORY_SEPARATOR,
                                    "/",
                                    $filePath);

            if (!array_key_exists($filePath,
                                  $this->cssFile)) {
                $this->cssFile[$filePath] = $options;
            }
        }
    }

    /**
     * Retourne les mots clès et la description de la page.
     *
     * @return string
     */
    private function getMetaKeywords(): string
    {
        $keywords = "";

        if (empty($this->keywords)) {
            $keywords = implode(", ",
                                $this->keywords);
        }

        if (CoreLoader::isCallable("CoreMain")) {
            if (empty($this->description)) {
                $this->description = CoreMain::getInstance()->getConfigs()->getDefaultDescription();
            }

            if (empty($keywords)) {
                $keywords = CoreMain::getInstance()->getConfigs()->getDefaultKeywords();
            }
        }

        $keywords = strip_tags($keywords);

        // 500 caractères maximum
        $keywords = (strlen($keywords) > 500) ? substr($keywords,
                                                       0,
                                                       500) : $keywords;

        return "<meta name=\"description\" content=\"" . ExecString::textDisplay($this->description) . "\" />\n"
            . "<meta name=\"keywords\" content=\"" . ExecString::textDisplay($keywords) . "\" />\n";
    }

    /**
     * Retourne les scripts à inclure.
     *
     * @param bool $forceIncludes Pour forcer l'inclusion des fichiers javascript.
     * @return string
     */
    private function &getMetaIncludeJavascript(bool $forceIncludes = false): string
    {
        if (CoreRequest::getRequestMethod() !== CoreRequestType::POST || $forceIncludes) {
            $fullScreen = CoreLoader::isCallable("CoreMain") ? CoreMain::getInstance()->isDefaultLayout() : true;

            if (($fullScreen || $forceIncludes) && $this->javascriptEnabled()) {
                if (!empty($this->javaScriptJquery)) {
                    $this->addJavascriptFile("jquery.js");
                }

                $this->addJavascriptFile("tr_engine.js");
            } else {
                $this->resetJavascript();
            }

            // Lorsque l'on ne force pas l'inclusion on fait un nouveau test
            if (!$forceIncludes) {
                if (!$this->javascriptEnabled() && !CoreSecure::getInstance()->locked()) {
                    $this->addJavascriptFile("javascriptenabled.js");
                    $this->addJavascriptCode("javascriptEnabled('" . $this->cookieTestName . "');");
                }
            }

            if (CoreLoader::isCallable("CoreMain")) {
                $coreMain = CoreMain::getInstance();

                if ($coreMain->getAgentInfos()->getBrowserName() === "Internet Explorer" && $coreMain->getAgentInfos()->getBrowserVersion() < "7") {
                    $this->addJavascriptFile("pngfix.js",
                                             "defer");
                }
            }
        } else {
            $this->resetJavascript();
        }

        $meta = "";

        // Conception de l'entête
        foreach ($this->javaScriptFile as $fileName => $options) {
            $meta .= "<script" . ((!empty($options)) ? " " . $options : "") . " type=\"text/javascript\" src=\"Resources/Js/" . $fileName . "\"></script>\n";
        }
        return $meta;
    }

    /**
     * Reset des codes et fichier inclus javascript.
     */
    private function resetJavascript()
    {
        $this->javaScriptCode = "";
        $this->javaScriptJquery = "";
        $this->javaScriptFile = array();
    }

    /**
     * Retourne les fichiers de css à inclure.
     *
     * @return string
     */
    private function &getMetaIncludeCss(): string
    {
        $this->addCssTemplateFile("engine.css");
        $this->addCssTemplateFile("main.css");

        $meta = "";

        // Conception de l'entête
        foreach ($this->cssFile as $filePath => $options) {
            if (!empty($options)) {
                $options = " " . $options;
            }

            $meta .= "<link rel=\"stylesheet\" href=\"" . $filePath . "\" type=\"text/css\" />\n";
        }
        return $meta;
    }

    /**
     * Execute les fonctions javascript demandées.
     *
     * @return string
     */
    private function &getMetaExecuteJavascript(): string
    {
        $script = "<script type=\"text/javascript\">\n";

        if (!empty($this->javaScriptCode)) {
            $script .= $this->javaScriptCode;
        }

        if (!empty($this->javaScriptJquery)) {
            $script .= "$(document).ready(function(){";
            $script .= $this->javaScriptJquery;
            $script .= "});";
        }

        $script .= "</script>\n";
        return $script;
    }
}