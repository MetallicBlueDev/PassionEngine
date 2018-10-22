<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Fail\FailBase;
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
     * Dossier principal contenant les dossiers templates.
     *
     * @var string
     */
    public const TEMPLATE_ROOT_DIRECTORY_NAME = "Template";

    /**
     * chemin vers le dossier principal contenant les dossiers templates.
     *
     * @var string
     */
    public const DEFAULT_TEMPLATE_ROOT_DIRECTORY = CoreLoader::ENGINE_SUBTYPE . DIRECTORY_SEPARATOR . self::TEMPLATE_ROOT_DIRECTORY_NAME;

    /**
     * Dossier contenant le template en cours d'utilisation.
     *
     * @var string
     */
    public const DEFAULT_TEMPLATE_DIRECTORY = self::DEFAULT_TEMPLATE_ROOT_DIRECTORY . DIRECTORY_SEPARATOR . "MetallicBlueSky";

    /**
     * Dossier contenant les fichiers templates en cours d'utilisation.
     * Attention, il faut laisser une valeur par défaut valide.
     *
     * @var string
     */
    private static $templateDirectory = self::DEFAULT_TEMPLATE_DIRECTORY;

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
     * Indique si l'instance courante est en mode sans échec.
     *
     * @var bool
     */
    private $debugMode = false;

    /**
     * Détermine si le fichier template est valide.
     * Par défaut, valeur nulle car non vérifié.
     *
     * @var bool
     */
    private $valid = null;

    /**
     * Nouveau fichier template.
     *
     * @param string $fileName Nom du template
     */
    public function __construct(string $fileName = "")
    {
        $this->setFileName($fileName);
    }

    /**
     * Assigne une valeur au template.
     *
     * @param string $key Nom de la variable
     * @param LibMakeStyle $value Valeur de la variable
     */
    public function assignStyle(string $key,
                                LibMakeStyle $value): void
    {
        $this->fileVars[$key] = $value->display();
    }

    /**
     * Assigne une chaine de caractères au template.
     *
     * @param string $key Nom de la variable
     * @param string $value Valeur de la variable
     */
    public function assignString(string $key,
                                 string $value): void
    {
        $this->fileVars[$key] = $value;
    }

    /**
     * Assigne un tableau au template.
     *
     * @param string $key Nom de la variable
     * @param string $value Valeur de la variable
     */
    public function assignArray(string $key,
                                array $value): void
    {
        $this->fileVars[$key] = $value;
    }

    /**
     * Exécute et affiche le fichier template.
     *
     * @param string $fileName Nom du template
     * @param bool $debugMode Si le fichier de template debug n'est pas trouvé, le fichier debug par défaut est utilisé.
     */
    public function display(string $fileName = "",
                            bool $debugMode = false): void
    {
        if ($debugMode) {
            $this->setFileName($fileName);

            // Le template ne contient pas le fichier debug
            if (!$this->isTemplateFile()) {
                // Activation du mode debug
                $this->setDebugMode();
            }
        }

        echo $this->render($fileName);
    }

    /**
     * Retourne le rendu du template.
     *
     * @param string $fileName Nom du template
     * @return string
     * @throws FailTemplate
     */
    public function &render(string $fileName = ""): string
    {
        $this->setFileName($fileName);

        // Vérification du template
        if (!$this->isTemplateFile()) {
            if ($this->debugMode) {
                exit("CRITICAL ERROR: DEBUG TEMPLATE NOT FOUND.");
            }

            throw new FailTemplate("invalid template file",
                                   FailBase::getErrorCodeName(17),
                                                              array($this->getTemplateFilePath()));
        }

        // Extrait les variables en local
        extract($this->fileVars);

        // Traitement du template
        ob_start();
        include $this->getTemplateFilePath();
        $output = ob_get_clean();
        return $output;
    }

    /**
     * Configure le dossier contenant les fichiers templates.
     *
     * @param string $directory
     * @throws FailTemplate
     */
    public static function configureTemplateDirectory(string $directory): void
    {
        if (!self::isTemplateDirectory($directory)) {
            throw new FailTemplate("invalid template directory",
                                   FailBase::getErrorCodeName(18),
                                                              array($directory));
        }

        self::$templateDirectory = $directory;
    }

    /**
     * Retourne le dossier vers les templates.
     *
     * @return string
     */
    public static function &getTemplateDirectory(): string
    {
        return self::$templateDirectory;
    }

    /**
     * Retourne la liste de dossiers templates disponibles.
     *
     * @return array
     */
    public static function &getTemplateList(): array
    {
        $templates = array();
        $templateDirectories = array(
            CoreLoader::CUSTOM_SUBTYPE . DIRECTORY_SEPARATOR . self::TEMPLATE_ROOT_DIRECTORY_NAME,
            CoreLoader::ENGINE_SUBTYPE . DIRECTORY_SEPARATOR . self::TEMPLATE_ROOT_DIRECTORY_NAME);

        foreach ($templateDirectories as $templateDirectory) {
            if (self::isTemplateDirectory($templateDirectory)) {
                $templates = array_merge($templateDirectories,
                                         CoreCache::getInstance()->getNameList($templateDirectory));
            }
        }
        return $templates;
    }

    /**
     * Détermine si le dossier contenant les fichiers template est valide.
     *
     * @param string $directory
     * @return bool
     */
    public static function isTemplateDirectory($directory)
    {
        return !empty($directory) && is_dir(TR_ENGINE_ROOT_DIRECTORY . DIRECTORY_SEPARATOR . $directory);
    }

    /**
     * Affecte le nom du fichier représentant le template.
     *
     * @param string $fileName Nom du fichier
     */
    private function setFileName(string $fileName): void
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
     * Activation du mode sans échec.
     */
    private function setDebugMode(): void
    {
        $this->debugMode = true;
        $this->valid = null;
    }

    /**
     * Retourne le chemin jusqu'au fichier template.
     *
     * @return string path
     */
    private function &getTemplateFilePath(): string
    {
        $path = "";

        if ($this->debugMode) {
            // En debug mode, on utilise le fichier par défaut
            $path = TR_ENGINE_ROOT_DIRECTORY . DIRECTORY_SEPARATOR . self::DEFAULT_TEMPLATE_ROOT_DIRECTORY . DIRECTORY_SEPARATOR . "makestyle.debug.php";
        } else {
            $path = TR_ENGINE_ROOT_DIRECTORY . DIRECTORY_SEPARATOR . self::$templateDirectory . DIRECTORY_SEPARATOR . $this->fileName;
        }
        return $path;
    }

    /**
     * Vérifie la validité du fichier template.
     *
     * @return bool true si le chemin du template est valide
     */
    private function isTemplateFile(): bool
    {
        if ($this->valid === null) {
            $this->valid = is_file($this->getTemplateFilePath());
        }
        return $this->valid;
    }
}