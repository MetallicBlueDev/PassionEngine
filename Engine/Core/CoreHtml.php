<?php

namespace PassionEngine\Engine\Core;

use PassionEngine\Engine\Lib\LibMakeStyle;
use PassionEngine\Engine\Exec\ExecString;
use PassionEngine\Engine\Exec\ExecCookie;
use PassionEngine\Engine\Core\CoreRequest;
use PassionEngine\Engine\Exec\ExecCrypt;

/**
 * Gestionnaire de contenu HTML.
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
    private $cookieTestName = 'test';

    /**
     * Détermine l'état du javaScript chez le client.
     * <ul>
     * <li>-1: désactivé explicitement chez le client.</li>
     * <li>0: état inconnu.</li>
     * <li>1: activé.</li>
     * </ul>
     *
     * @var int
     */
    private $javaScriptMode = 0;

    /**
     * Fonctions et codes javaScript demandées.
     *
     * @var string
     */
    private $javaScriptCode = '';

    /**
     * Fonctionnalités de script ne sont pas prises en charge.
     *
     * @var string
     */
    private $noScriptCode = '';

    /**
     * Fonctions et codes javaScript JQUERY demandées.
     *
     * @var string
     */
    private $javaScriptJquery = '';

    /**
     * Fichiers de javaScript demandées.
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
    private $title = '';

    /**
     * Mots clés de la page courante.
     *
     * @var array
     */
    private $keywords = array();

    /**
     * Description de la page courante.
     *
     * @var string
     */
    private $description = '';

    /**
     * Nouveau gestionnaire.
     */
    private function __construct()
    {
        // Configuration du préfixe accessible
        if (CoreLoader::isCallable('CoreMain')) {
            $prefix = CoreMain::getInstance()->getConfigs()->getCookiePrefix();
        } else {
            $prefix = 'pe';
        }

        // Composition du nom du cookie de test
        $this->cookieTestName = ExecCrypt::cryptByStandard(
                $prefix . '_' . $this->cookieTestName,
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
    public static function checkInstance(): void
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
    public function addJavascriptJquery(string $javaScript): void
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
    public function addJavascriptCode(string $javaScript): void
    {
        if (!empty($this->javaScriptCode)) {
            $this->javaScriptCode .= "\n";
        }

        $this->javaScriptCode .= $javaScript;
    }

    /**
     * Ajoute d'une fonctionnalité qui n'est pas prise en charge.
     *
     * @param string $noScript
     */
    public function addNoScriptCode(string $noScript): void
    {
        $this->noScriptCode .= $noScript . "\n";
    }

    /**
     * Ajoute un code javaScript (compatible JQUERY et javaScript pur) à exécuter.
     *
     * @param string $javaScript
     */
    public function addJavascript(string $javaScript): void
    {
        if ($this->javascriptEnabled() && CoreLoader::isCallable('CoreMain') && CoreMain::getInstance()->getRoute()->isDefaultLayout()) {
            $this->addJavascriptJquery($javaScript);
        } else {
            $this->addJavascriptCode($javaScript);
        }
    }

    /**
     * Retourne l'état du javaScript du client.
     *
     * @return bool
     */
    public function javascriptEnabled(): bool
    {
        return $this->javaScriptMode > -1;
    }

    /**
     * Ajoute un fichier javaScript à l'entête.
     *
     * @param string $fileName
     * @param string $options
     */
    public function addJavascriptFile(string $fileName,
                                      string $options = ''): void
    {
        if (!array_key_exists($fileName,
                              $this->javaScriptFile)) {
            if ($fileName === 'jquery.js') {
                // Fixe JQuery en 1ere position
                $this->javaScriptFile = array_merge(array(
                    $fileName => $options),
                                                    $this->javaScriptFile);
            } else if ($fileName === 'tr_engine.js') {
                // Fixe tr_engine en 2em position
                if (array_key_exists('jquery.js',
                                     $this->javaScriptFile)) {
                    $this->javaScriptFile = array_merge(array(
                        'jquery.js' => $this->javaScriptFile['jquery.js'],
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
     * Ajoute une feuille de style CSS à l'entête.
     *
     * @param string $fileName
     * @param string $options
     */
    public function addCssResourceFile(string $fileName,
                                       string $options = ''): void
    {
        $this->addCssFile('Resources' . DIRECTORY_SEPARATOR . 'Css' . DIRECTORY_SEPARATOR . $fileName,
                          $options);
    }

    /**
     * Ajoute un fichier de style .CSS provenant du dossier template à l'entête.
     *
     * @param string $fileName
     * @param string $options
     */
    public function addCssTemplateFile(string $fileName,
                                       string $options = ''): void
    {
        if (CoreLoader::isCallable('LibMakeStyle')) {
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
        $title = '';

        if (CoreLoader::isCallable('CoreMain')) {
            $coreMain = CoreMain::getInstance();
            $title = $coreMain->getConfigs()->getDefaultSiteName();

            if (empty($this->title)) {
                // Titre automatique
                $title .= ' - ' . $coreMain->getConfigs()->getDefaultSiteSlogan();

                if (CoreLoader::isCallable('LibModule')) {
                    $title .= ' / ' . CoreMain::getInstance()->getRoute()->getRequestedModuleData()->getName();
                }
            } else {
                // Titre manuel
                $title = $this->title . ' - ' . $title;
            }
        } else {
            // Titre en mode dégradé
            $title .= CoreRequest::getString('SERVER_NAME',
                                             '',
                                             CoreRequestType::SERVER);

            if (!empty($this->title)) {
                $title .= ' - ' . $this->title;
            }
        }
        return ExecString::textDisplay($title);
    }

    /**
     * Retourne les métas données de l'entête HTML.
     *
     * @link https://developer.mozilla.org/fr/docs/Web/HTML/Element/meta
     * @return string
     */
    public function getMetaHeaders(): string
    {
        //TODO ajouter un support RSS XML
        return $this->getMetaKeywords()
            . '<meta name="generator" content="PassionEngine" />' . "\n"
            . '<meta charset="utf-8" />' . "\n"
            . '<link rel="canonical" href="https://' . PASSION_ENGINE_URL . '" />' . "\n"
            . '<link rel="shortcut icon" type="image/x-icon" href="' . LibMakeStyle::getTemplateDirectory() . '/favicon.ico" />' . "\n"
            . $this->getMetaNoScript()
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
    public function setTitle(string $title): void
    {
        $this->title = strip_tags($title);
    }

    /**
     * Affecte les mots clés de la page courante.
     *
     * @param array $keywords un tableau de mots clés
     */
    public function setKeywords(array $keywords): void
    {
        if (!empty($this->keywords)) {
            array_push($this->keywords,
                       $keywords);
        } else {
            $this->keywords = $keywords;
        }
    }

    /**
     * Affecte la description de la page courante.
     * Résumé concis et pertinent du contenu de la page.
     *
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = strip_tags($description);
    }

    /**
     * Retourne un lien HTML sans javaScript.
     *
     * @link https://developer.mozilla.org/fr/docs/Web/HTML/Element/a
     * @param string $link Adresse URL de base.
     * @param string $displayContent Données à afficher (texte simple ou code HTML)
     * @param string $onclick Données à exécuter lors du clique
     * @param string $addons Code additionnel
     * @return string
     */
    public static function &getLink(string $link,
                                    string $displayContent = '',
                                    string $onclick = '',
                                    string $addons = ''): string
    {
        $htmlLink = '<a href="' . CoreUrlRewriting::getLink($link) . '"';

        // TODO A vérifier /^[A-Za-z0-9.-\s]+$/ie pas compatible avec les traductions
        if (preg_match('/^[A-Za-z0-9.\s]+$/ie',
                       $displayContent)) {
            $htmlLink .= ' title="' . $displayContent . '"';
        }

        if (!empty($onclick)) {
            $htmlLink .= ' onclick="' . $onclick . '"';
        }

        if (!empty($addons)) {
            $htmlLink .= ' ' . $addons;
        }

        $htmlLink .= '>';

        if (!empty($displayContent)) {
            $htmlLink .= $displayContent;
        }

        $htmlLink .= '</a>';
        return $htmlLink;
    }

    /**
     * Redirection ou chargement via javaScript vers une page.
     *
     * @param string $url La page demandée à charger.
     * @param int $tps Temps avant le chargement de la page.
     * @param string $method Identifiant de la division de destination si ce n'est pas toute la page.
     */
    public function redirect(string $url = '',
                             int $tps = 0,
                             string $method = 'window'): void
    {
        // Configuration de l'url
        if (empty($url) || $url === 'index.php?') {
            $url = 'index.php';
        }

        // Redirection
        if ($this->javascriptEnabled() && ($tps > 0 || $method !== 'windows')) {
            // Configuration du temps
            $tps = $tps * 1000;

            if (CoreRequest::getRequestMethod() === CoreRequestType::POST && $method !== 'window') {
                // Commande ajax pour la redirection
                $this->addJavascriptCode('setTimeout(function(){ $(\'' . $method . '\').load(\'' . $url . '\'); }, $tps);');
            } else {
                // Commande par défaut
                $this->addJavascriptCode('setTimeout(\'window.location = \'' . $url . '\'\', \'' . $tps . '\');');
            }
        } else {
            // Redirection php sans timeout
            header('Location: ' . $url);
        }
    }

    /**
     * Inclus et exécute le javaScript de façon autonome.
     */
    public function selfJavascript(): void
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
        $rslt = '';

        if ($this->javascriptEnabled()) {
            $libMakeStyle = new LibMakeStyle('loader');
            $rslt = '<div id="loader">' . $libMakeStyle->render() . '</div>';
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
        // Configuration de la clé si accessible
        if (CoreLoader::isCallable('CoreMain')) {
            $key = CoreMain::getInstance()->getConfigs()->getCryptKey();
        } else {
            $key = 'A4bT9D4V';
        }
        return $key;
    }

    /**
     * Détection du javaScript chez le client.
     */
    private function checkJavascriptEnabled(): void
    {
        $this->javaScriptMode = $this->requestJavascriptMode();

        if ($this->javaScriptMode === 0 && !CoreSecure::getInstance()->locked()) {

            $this->addNoScriptCode('<meta http-equiv="refresh" content="1;url=http://' . PASSION_ENGINE_URL . '/index.php?' . CoreRequest::getQueryString() . '&javaScriptMode=-1">');
            $this->javaScriptMode = 1;
        }
    }

    /**
     * Détection du javaScript chez le client.
     *
     * @return int
     */
    private function requestJavascriptMode(): int
    {
        $newMode = CoreRequest::getInteger('javaScriptMode');
        $currentMode = ExecCookie::getCookie($this->cookieTestName);

        if (empty($currentMode) && $newMode === -1) {
            $currentMode = '-1';

            if (!ExecCookie::createCookie($this->cookieTestName,
                                          $newMode)) {
                CoreLogger::addException('Fail to create cookie with javaScriptMode.');
            }
        }
        return ($currentMode === '-1') ? -1 : 0;
    }

    /**
     * Ajoute un fichier de style .CSS à l'entête.
     *
     * @param string $filePath
     * @param string $options
     */
    private function addCssFile(string $filePath,
                                string $options = ''): void
    {
        if (is_file(PASSION_ENGINE_ROOT_DIRECTORY . DIRECTORY_SEPARATOR . str_replace('/',
                                                                                      DIRECTORY_SEPARATOR,
                                                                                      $filePath))) {
            $filePath = str_replace(DIRECTORY_SEPARATOR,
                                    '/',
                                    $filePath);

            if (!array_key_exists($filePath,
                                  $this->cssFile)) {
                $this->cssFile[$filePath] = $options;
            }
        }
    }

    /**
     * Retourne les mots clés et la description de la page.
     *
     * @link https://developer.mozilla.org/fr/docs/Web/HTML/Element/meta
     * @return string
     */
    private function getMetaKeywords(): string
    {
        // Contient une liste de mots-clés séparés par des virgules.
        $keywords = '';

        if (!empty($this->keywords)) {
            $keywords = implode(',',
                                $this->keywords);
        }

        if (CoreLoader::isCallable('CoreMain')) {
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

        return '<meta name="description" content="' . ExecString::textDisplay($this->description) . '" />' . "\n"
            . '<meta name="keywords" content="' . ExecString::textDisplay($keywords) . '" />' . "\n";
    }

    /**
     * Retourne les scripts à inclure.
     *
     * @link https://developer.mozilla.org/fr/docs/Web/HTML/Element/script
     * @param bool $forceIncludes Pour forcer l'inclusion des fichiers javaScript.
     * @return string
     */
    private function &getMetaIncludeJavascript(bool $forceIncludes = false): string
    {
        if (CoreRequest::getRequestMethod() !== CoreRequestType::POST || $forceIncludes) {
            $this->checkMetaIncludeJavascript($forceIncludes);
        } else {
            $this->resetJavascript();
        }

        $meta = '';

        // Conception de l'entête
        foreach ($this->javaScriptFile as $fileName => $options) {
            $meta .= '<script' . ((!empty($options)) ? ' ' . $options : '') . ' type="text/javascript" src="Resources/Js/' . $fileName . '"></script>' . "\n";
        }
        return $meta;
    }

    /**
     * Vérification des scripts à inclure.
     *
     * @param bool $forceIncludes
     */
    private function checkMetaIncludeJavascript(bool $forceIncludes = false): void
    {
        $fullScreen = CoreLoader::isCallable('CoreMain') ? CoreMain::getInstance()->getRoute()->isDefaultLayout() : true;

        if (($fullScreen || $forceIncludes) && $this->javascriptEnabled()) {
            if (!empty($this->javaScriptJquery)) {
                $this->addJavascriptFile('jquery.js');
            }

            $this->addJavascriptFile('tr_engine.js');
        } else {
            $this->resetJavascript();
        }

        if (CoreLoader::isCallable('CoreMain')) {
            $coreMain = CoreMain::getInstance();

            if ($coreMain->getAgentInfos()->getBrowserName() === 'Internet Explorer' && $coreMain->getAgentInfos()->getBrowserVersion() < '7') {
                $this->addJavascriptFile('pngfix.js',
                                         'defer');
            }
        }
    }

    /**
     * Nettoyage des codes et fichier inclus javaScript.
     */
    private function resetJavascript(): void
    {
        $this->javaScriptCode = '';
        $this->javaScriptJquery = '';
        $this->javaScriptFile = array();
    }

    /**
     * Retourne les fichiers de CSS à inclure.
     *
     * @link https://developer.mozilla.org/fr/docs/Web/HTML/Element/link
     * @return string
     */
    private function &getMetaIncludeCss(): string
    {
        $this->addCssTemplateFile('engine.css');
        $this->addCssTemplateFile('main.css');

        $meta = '';

        // Conception de l'entête
        foreach ($this->cssFile as $filePath => $options) {
            if (!empty($options)) {
                $options = ' ' . $options;
            }

            $meta .= '<link rel="stylesheet" type="text/css" href="' . $filePath . '" />' . "\n";
        }
        return $meta;
    }

    /**
     * Retourne le script d'exécution des fonctions javaScript demandées.
     *
     * @link https://developer.mozilla.org/fr/docs/Web/HTML/Element/script
     * @return string
     */
    private function &getMetaExecuteJavascript(): string
    {
        $script = '<script type="text/javascript">' . "\n";

        if (!empty($this->javaScriptCode)) {
            $script .= $this->javaScriptCode;
        }

        if (!empty($this->javaScriptJquery)) {
            $script .= '$(document).ready(function(){';
            $script .= $this->javaScriptJquery;
            $script .= '});';
        }

        $script .= '</script>' . "\n";
        return $script;
    }

    /**
     * Retourne le script des fonctionnalités ne sont pas prises en charge.
     *
     * @link https://developer.mozilla.org/fr/docs/Web/HTML/Element/noscript
     * @return string
     */
    private function &getMetaNoScript(): string
    {
        $script = '';

        if (!empty($this->noScriptCode)) {
            $script = '<noscript>' . "\n"
                . $this->noScriptCode
                . '</noscript>' . "\n";
        }
        return $script;
    }
}