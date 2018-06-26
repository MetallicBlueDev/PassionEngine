<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreSecure;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreLoader;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Make Style, moteur de template PHP.
 * RAPIDE, SIMPLE ET EFFICACE !
 *
 * @author Sébastien Villemain
 */
class LibMakeStyle {

    /**
     * Dossier contenant les templates.
     *
     * @var string
     */
    private const TEMPLATE_DIRECTORY = "Template";

    /**
     * Dossier contenant le template en cours d'utilisation.
     *
     * @var string
     */
    private static $templateDir = CoreLoader::ENGINE_SUBTYPE . DIRECTORY_SEPARATOR . TEMPLATE_DIRECTORY . DIRECTORY_SEPARATOR . "MetallicBlueSky";

    /**
     * Nom du fichier template.
     *
     * @var string
     */
    private $fileName = "";

    /**
     * Variables assignées.
     *
     * @var array
     */
    private $fileVars = array();

    /**
     * Indique si l'instance courante est en mode debug.
     *
     * @var bool
     */
    private $debugMode = false;

    /**
     * Détermine si le template est valide.
     *
     * @var bool
     */
    private $valid = null;

    /**
     * Nouveau template.
     *
     * @param string $fileName nom du template
     */
    public function __construct(string $fileName = "") {
        $this->setFileName($fileName);
    }

    /**
     * Assigne une valeur au template.
     *
     * @param string $key Nome de la variable
     * @param LibMakeStyle $value Valeur de la variable
     */
    public function assignStyle(string $key, LibMakeStyle $value) {
        $this->fileVars[$key] = $value->display();
    }

    /**
     * Assigne une chaine de caractères au template.
     *
     * @param string $key Nome de la variable
     * @param string $value Valeur de la variable
     */
    public function assignString(string $key, string $value) {
        $this->fileVars[$key] = $value;
    }

    /**
     * Assigne un tableau au template.
     *
     * @param string $key Nome de la variable
     * @param string $value Valeur de la variable
     */
    public function assignArray(string $key, array $value) {
        $this->fileVars[$key] = $value;
    }

    /**
     * Exécute et affiche le template.
     *
     * @param string $fileName
     * @param bool $debugMode Si le fichier de template debug n'est pas trouvé, le fichier debug par défaut est utilisé.
     */
    public function display(string $fileName = "", bool $debugMode = false) {
        if ($debugMode) {
            $this->setFileName($fileName);

            // Le template ne contient pas le fichier debug
            if (!$this->isTemplate()) {
                // Activation du mode debug
                $this->setDebugMode();
            }
        }

        echo $this->render($fileName);
    }

    /**
     * Retourne le rendu du template.
     *
     * @param string $fileName
     * @return string
     */
    public function &render(string $fileName = ""): string {
        $this->setFileName($fileName);

        // Vérification du template
        if (!$this->isTemplate()) {
            if ($this->debugMode) {
                exit("CRITICAL ERROR: DEBUG TEMPLATE NOT FOUND.");
            }

            CoreSecure::getInstance()->throwException("makeStyle", null, array(
                $this->getTemplatePath()));
        }

        // Extrait les variables en local
        extract($this->fileVars);

        // Traitement du template
        ob_start();
        include $this->getTemplatePath();
        $output = ob_get_clean();
        return $output;
    }

    /**
     * Configure le dossier contenant les templates.
     *
     * @param string $templateDir
     */
    public static function setTemplateDir(string $templateDir) {
        if (!self::isTemplateDir($templateDir)) {
            CoreSecure::getInstance()->throwException("makeStyleConfig", null, array(
                "templateDir = " . $templateDir));
        }

        self::$templateDir = $templateDir;
    }

    /**
     * Retourne le dossier vers les templates.
     *
     * @return string
     */
    public static function &getTemplateDir(): string {
        return self::$templateDir;
    }

    /**
     * Retourne la liste de templates disponibles.
     *
     * @return array
     */
    public static function &getTemplateList(): array {
        $templates = array();
        $templatesDir = array(
            CoreLoader::CUSTOM_SUBTYPE . DIRECTORY_SEPARATOR . TEMPLATE_DIRECTORY,
            CoreLoader::ENGINE_SUBTYPE . DIRECTORY_SEPARATOR . TEMPLATE_DIRECTORY);

        foreach ($templatesDir as $templateDir) {
            if (self::isTemplateDir($templateDir)) {
                $templates = array_merge($templatesDir, CoreCache::getInstance()->getNameList($templateDir));
            }
        }
        return $templates;
    }

    /**
     * Détermine si le dossier contenant le template est valide.
     *
     * @param string $templateDir
     * @return bool
     */
    public static function isTemplateDir($templateDir) {
        return !empty($templateDir) && is_dir(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $templateDir);
    }

    /**
     * Affecte le nom du fichier représentant le template.
     *
     * @param string $fileName Nom du fichier
     */
    private function setFileName(string $fileName) {
        if (!empty($fileName)) {
            if (substr($fileName, -4) !== ".php") {
                $fileName .= ".php";
            }

            if ($this->fileName !== $fileName) {
                $this->fileName = $fileName;
                $this->valid = null;
            }
        }
    }

    /**
     * Activation du mode debug.
     */
    private function setDebugMode() {
        $this->debugMode = true;
        $this->valid = null;
    }

    /**
     * Retourne le chemin jusqu'au template.
     *
     * @return string path
     */
    private function &getTemplatePath(): string {
        $path = "";

        if ($this->debugMode) {
            // En debug mode, on utilise le fichier par défaut
            $path = TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . CoreLoader::ENGINE_SUBTYPE . DIRECTORY_SEPARATOR . TEMPLATE_DIRECTORY . DIRECTORY_SEPARATOR . "makestyle.debug.php";
        } else {
            $path = TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . self::$templateDir . DIRECTORY_SEPARATOR . $this->fileName;
        }
        return $path;
    }

    /**
     * Vérifie la validité du template.
     *
     * @return bool true si le chemin du template est valide
     */
    private function isTemplate(): bool {
        if ($this->valid === null) {
            $this->valid = is_file($this->getTemplatePath());
        }
        return $this->valid;
    }
}