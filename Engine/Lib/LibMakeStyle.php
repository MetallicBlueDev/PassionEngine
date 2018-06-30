<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreSecure;
use TREngine\Engine\Fail\FailTemplate;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreLoader;

/**
 * Make Style, moteur de template PHP.
 * RAPIDE, SIMPLE ET EFFICACE !
 *
 * @author Sébastien Villemain
 */
class LibMakeStyle
{

    /**
     * Dossier contenant les templates.
     *
     * @var string
     */
    public const TEMPLATE_ROOT_DIRECTORY = "Template";

    /**
     * Dossier contenant le template en cours d'utilisation.
     *
     * @var string
     */
    public const DEFAULT_TEMPLATE = CoreLoader::ENGINE_SUBTYPE . DIRECTORY_SEPARATOR . self::TEMPLATE_ROOT_DIRECTORY . DIRECTORY_SEPARATOR . "MetallicBlueSky";

    /**
     * Dossier contenant le template en cours d'utilisation.
     *
     * @var string
     */
    public static $templateDir = "";

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
    public function __construct(string $fileName = "")
    {
        $this->setFileName($fileName);
    }

    /**
     * Assigne une valeur au template.
     *
     * @param string $key Nome de la variable
     * @param LibMakeStyle $value Valeur de la variable
     */
    public function assignStyle(string $key, LibMakeStyle $value)
    {
        $this->fileVars[$key] = $value->display();
    }

    /**
     * Assigne une chaine de caractères au template.
     *
     * @param string $key Nome de la variable
     * @param string $value Valeur de la variable
     */
    public function assignString(string $key, string $value)
    {
        $this->fileVars[$key] = $value;
    }

    /**
     * Assigne un tableau au template.
     *
     * @param string $key Nome de la variable
     * @param string $value Valeur de la variable
     */
    public function assignArray(string $key, array $value)
    {
        $this->fileVars[$key] = $value;
    }

    /**
     * Exécute et affiche le template.
     *
     * @param string $fileName
     * @param bool $debugMode Si le fichier de template debug n'est pas trouvé, le fichier debug par défaut est utilisé.
     */
    public function display(string $fileName = "", bool $debugMode = false)
    {
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
    public function &render(string $fileName = ""): string
    {
        $this->setFileName($fileName);

        // Vérification du template
        if (!$this->isTemplate()) {
            if ($this->debugMode) {
                exit("CRITICAL ERROR: DEBUG TEMPLATE NOT FOUND.");
            }

            CoreSecure::getInstance()->catchException(new FailTemplate("invalid template file",
                                                                       17,
                                                                       array($this->getTemplatePath())));
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
    public static function setTemplateDirectory(string $templateDir)
    {
        if (!self::isTemplateDirectory($templateDir)) {
            CoreSecure::getInstance()->catchException(new FailTemplate("invalid template directory"),
                                                                       18,
                                                                       array($templateDir));
        }

        self::$templateDir = $templateDir;
    }

    /**
     * Retourne le dossier vers les templates.
     *
     * @return string
     */
    public static function &getTemplateDirectory(): string
    {
        return self::$templateDir;
    }

    /**
     * Retourne la liste de templates disponibles.
     *
     * @return array
     */
    public static function &getTemplateList(): array
    {
        $templates = array();
        $templateDirectories = array(
            CoreLoader::CUSTOM_SUBTYPE . DIRECTORY_SEPARATOR . self::TEMPLATE_ROOT_DIRECTORY,
            CoreLoader::ENGINE_SUBTYPE . DIRECTORY_SEPARATOR . self::TEMPLATE_ROOT_DIRECTORY);

        foreach ($templateDirectories as $templateDirectory) {
            if (self::isTemplateDirectory($templateDirectory)) {
                $templates = array_merge($templateDirectories,
                                         CoreCache::getInstance()->getNameList($templateDirectory));
            }
        }
        return $templates;
    }

    /**
     * Détermine si le dossier contenant le template est valide.
     *
     * @param string $templateDirectory
     * @return bool
     */
    public static function isTemplateDirectory($templateDirectory)
    {
        return !empty($templateDirectory) && is_dir(TR_ENGINE_INDEX_DIRECTORY . DIRECTORY_SEPARATOR . $templateDirectory);
    }

    /**
     * Affecte le nom du fichier représentant le template.
     *
     * @param string $fileName Nom du fichier
     */
    private function setFileName(string $fileName)
    {
        if (!empty($fileName)) {
            if (substr($fileName,
                       -4) !== ".php") {
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
    private function setDebugMode()
    {
        $this->debugMode = true;
        $this->valid = null;
    }

    /**
     * Retourne le chemin jusqu'au template.
     *
     * @return string path
     */
    private function &getTemplatePath(): string
    {
        $path = "";

        if ($this->debugMode) {
            // En debug mode, on utilise le fichier par défaut
            $path = TR_ENGINE_INDEX_DIRECTORY . DIRECTORY_SEPARATOR . CoreLoader::ENGINE_SUBTYPE . DIRECTORY_SEPARATOR . self::TEMPLATE_ROOT_DIRECTORY . DIRECTORY_SEPARATOR . "makestyle.debug.php";
        } else {
            $path = TR_ENGINE_INDEX_DIRECTORY . DIRECTORY_SEPARATOR . self::$templateDir . DIRECTORY_SEPARATOR . $this->fileName;
        }
        return $path;
    }

    /**
     * Vérifie la validité du template.
     *
     * @return bool true si le chemin du template est valide
     */
    private function isTemplate(): bool
    {
        if ($this->valid === null) {
            $this->valid = is_file($this->getTemplatePath());
        }
        return $this->valid;
    }
}