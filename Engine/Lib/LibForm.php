<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreCacheSection;
use TREngine\Engine\Exec\ExecString;

/**
 * Classe de mise en forme d'un formulaire.
 * Avec mise en cache automatique.
 *
 * @author Sébastien Villemain
 */
class LibForm
{

    /**
     * Nom de la variable en cache.
     */
    private const CACHE_VARIABLE_NAME = "vars";

    /**
     * Nom du formulaire.
     *
     * @var string
     */
    private $name = "";

    /**
     * Url pour le tag d'action.
     *
     * @var string
     */
    private $urlAction = "";

    /**
     * Titre du formulaire.
     *
     * @var string
     */
    private $title = "";

    /**
     * Description du formulaire.
     *
     * @var string
     */
    private $description = "";

    /**
     * Donnée contenu dans le formulaire.
     *
     * @var string
     */
    private $inputData = "";

    /**
     * Fermeture de la balise fieldset.
     *
     * @var bool
     */
    private $doFieldset = true;

    /**
     * Formulaire présent dans le cache.
     *
     * @var bool
     */
    private $cached = false;

    /**
     * Variable mise en cache.
     *
     * @var array
     */
    private $cacheVars = array();

    /**
     * Position dans le tableau des variables cache.
     *
     * @var int
     */
    private $cacheVarsIndex = 0;

    /**
     * Nouveau formulaire.
     *
     * @param string $name identifiant du formlaire : attention à ne pas prendre un nom déjà utilisé !
     * @param string $urlAction
     */
    public function __construct(string $name,
                                string $urlAction = "")
    {
        $this->name = $name;
        $this->urlAction = !empty($urlAction) ? $urlAction : "index.php";
        $this->cached = CoreCache::getInstance(CoreCacheSection::FORMS)->cached($name . ".php");
    }

    /**
     * Affecte un title au début du formulaire.
     *
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Affecte une description au début du formulaire.
     *
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Ajoute un fieldset.
     *
     * @param string $title
     * @param string $description
     */
    public function addFieldset(string $title = "",
                                string $description = ""): void
    {
        $title = $this->getTitle($title);
        $description = $this->getDescription($description);

        if (!$this->cached) {
            if (!$this->doFieldset) {
                $this->doFieldset = true;
            } else {
                $this->inputData .= "</fieldset>";
            }

            $this->inputData .= "<fieldset class=\"fieldset\">"
                . $title . $description;
        }
    }

    /**
     * Ajoute un champs de type texte.
     *
     * @param string $name
     * @param string $description
     * @param string $defaultValue
     * @param string $options
     * @param string $class
     */
    public function addInputText(string $name,
                                 string $description = "",
                                 string $defaultValue = "",
                                 string $options = "",
                                 string $class = ""): void
    {
        $this->addInput($name,
                        $name,
                        $description,
                        "text",
                        $defaultValue,
                        $options,
                        $class);
    }

    /**
     * Ajoute un champs masqué.
     *
     * @param string $name
     * @param string $defaultValue
     * @param string $options
     */
    public function addInputHidden(string $name,
                                   string $defaultValue,
                                   string $options = ""): void
    {
        $this->addInput($name,
                        $name,
                        "",
                        "hidden",
                        $defaultValue,
                        $options,
                        "");
    }

    /**
     * Ajoute un bouton d'envoi.
     *
     * @param string $name
     * @param string $defaultValue
     * @param string $options
     * @param string $class
     */
    public function addInputSubmit(string $name,
                                   string $defaultValue,
                                   string $options = "",
                                   string $class = ""): void
    {
        $this->addInput($name,
                        $name,
                        "",
                        "submit",
                        $defaultValue,
                        $options,
                        $class);
    }

    /**
     * Ajoute un champs de type bouton radio.
     *
     * @param string $id
     * @param string $name
     * @param string $description
     * @param bool $checked
     * @param string $defaultValue
     * @param string $options
     * @param string $class
     */
    public function addInputRadio(string $id,
                                  string $name,
                                  string $description = "",
                                  bool $checked = false,
                                  string $defaultValue = "",
                                  string $options = "",
                                  string $class = ""): void
    {
        if (empty($class)) {
            $class = "radio";
        }

        if ($checked) {
            $options = "checked=\"checked\"" . ((!empty($options)) ? " " . $options : "");
        }

        $this->addInput($id,
                        $name,
                        $description,
                        "radio",
                        $defaultValue,
                        $options,
                        $class);
    }

    /**
     * Ajoute un champs de type case à cocher.
     *
     * @param string $id
     * @param string $name
     * @param string $description
     * @param bool $checked
     * @param string $defaultValue
     * @param string $options
     * @param string $class
     */
    public function addInputCheckbox(string $id,
                                     string $name,
                                     string $description = "",
                                     bool $checked = false,
                                     string $defaultValue = "",
                                     string $options = "",
                                     string $class = ""): void
    {
        if (empty($class)) {
            $class = "checkbox";
        }

        if ($checked) {
            $options = "checked=\"checked\"" . ((!empty($options)) ? " " . $options : "");
        }

        $this->addInput($id,
                        $name,
                        $description,
                        "checkbox",
                        $defaultValue,
                        $options,
                        $class);
    }

    /**
     * Ajoute un bouton.
     *
     * @param string $name
     * @param string $description
     * @param string $defaultValue
     * @param string $options
     * @param string $class
     */
    public function addInputButton(string $name,
                                   string $description = "",
                                   string $defaultValue = "",
                                   string $options = "",
                                   string $class = ""): void
    {
        $this->addInput($name,
                        $name,
                        $description,
                        "button",
                        $defaultValue,
                        $options,
                        $class);
    }

    /**
     * Ajoute un champs de type mot de passe.
     *
     * @param string $name
     * @param string $description
     * @param string $options
     * @param string $class
     */
    public function addInputPassword(string $name,
                                     string $description = "",
                                     string $options = "",
                                     string $class = ""): void
    {
        $this->addInput($name,
                        $name,
                        $description,
                        "password",
                        "",
                        $options,
                        $class);
    }

    /**
     * Ajoute un textarea.
     *
     * @param string $name
     * @param string $description
     * @param string $defaultValue
     * @param string $options
     * @param string $class
     */
    public function addTextarea(string $name,
                                string $description,
                                string $defaultValue = "",
                                string $options = "",
                                string $class = ""): void
    {
        if (empty($class)) {
            $class = "textarea";
        }

        $idDescription = $this->getId($name);
        $description = $this->getLabel($name,
                                       $description);
        $id = $this->getId($name,
                           "input");

        $this->addCacheVar($name);
        $name = $this->getLastCacheVar();

        $this->addCacheVar($class);
        $class = $this->getLastCacheVar();

        $this->addCacheVar($options);
        $options = $this->getLastCacheVar();

        $defaultValue = ExecString::stripSlashes($defaultValue);
        $this->addCacheVar($defaultValue);
        $defaultValue = $this->getLastCacheVar();

        if (!$this->cached) {
            $this->inputData .= "<p id=\"" . $idDescription . "\">" . $description
                . " <textarea id=\"" . $id . "\" name=\"" . $name . "\""
                . " class=\"" . $class . "\" " . $options . " >"
                . $defaultValue . "</textarea></p>";
        }
    }

    /**
     * Ajoute un liste déroulante (balise ouvrante seulement).
     *
     * @param string $name
     * @param string $description
     * @param string $options
     * @param string $class
     */
    public function addSelectOpenTag(string $name,
                                     string $description,
                                     string $options = "",
                                     string $class = ""): void
    {
        if (empty($class)) {
            $class = "select";
        }

        $idDescription = $this->getId($name);
        $description = $this->getLabel($name,
                                       $description);
        $id = $this->getId($name,
                           "input");

        $this->addCacheVar($name);
        $name = $this->getLastCacheVar();

        $this->addCacheVar($class);
        $class = $this->getLastCacheVar();

        $this->addCacheVar($options);
        $options = $this->getLastCacheVar();

        if (!$this->cached) {
            $this->inputData .= "<p id=\"" . $idDescription . "\">" . $description
                . " <select id=\"" . $id . "\" name=\"" . $name . "\""
                . " class=\"" . $class . "\" " . $options . ">";
        }
    }

    /**
     * Ajoute un élément dans la liste déroulante ouverte.
     *
     * @param string $value
     * @param string $description
     * @param bool $selected
     * @param string $options
     */
    public function addSelectItemTag(string $value,
                                     string $description = "",
                                     bool $selected = false,
                                     string $options = ""): void
    {
        if ($selected) {
            $options = "selected=\"selected\"" . ((!empty($options)) ? " " . $options : "");
        }

        $description = ((!empty($description)) ? ExecString::textDisplay($description) : $value);

        $this->addCacheVar($value);
        $value = $this->getLastCacheVar();

        $this->addCacheVar($options);
        $options = $this->getLastCacheVar();

        $this->addCacheVar($description);
        $description = $this->getLastCacheVar();

        if (!$this->cached) {
            $this->inputData .= " <option value=\"" . $value . "\" " . $options . ">"
                . $description . "</option>";
        }
    }

    /**
     * Ajoute la fermeture de la liste déroulante.
     */
    public function addSelectCloseTag(): void
    {
        if (!$this->cached) {
            $this->inputData .= "</select></p>";
        }
    }

    /**
     * Ajoute du code HTML en plus dans le fieldset courant.
     *
     * @param string $html
     */
    public function addHtmlInFieldset(string $html): void
    {
        $this->addCacheVar($html);

        if (!$this->cached) {
            $this->inputData .= "<p>" . $this->getLastCacheVar() . "</p>";
        }
    }

    /**
     * Ajoute un espace.
     */
    public function addSpace(): void
    {
        if (!$this->cached) {
            $this->inputData .= "<p><br /></p>";
        }
    }

    /**
     * Ajoute du code HTML en plus après avoir fermé le fieldset courant.
     *
     * @param string $html
     */
    public function addHtmlOutFieldset(string $html): void
    {
        $this->addCacheVar($html);

        if (!$this->cached) {
            if ($this->doFieldset) {
                $this->inputData .= "</fieldset>";
                $this->doFieldset = false;
            }

            $this->inputData .= "<p>" . $this->getLastCacheVar() . "</p>";
        }
    }

    /**
     * Retourne le rendu du formulaire complet.
     * Attention, ceci procédera a une sauvegarde et une lecture du cache.
     *
     * @param string $class
     * @return string
     */
    public function &render(string $class = ""): string
    {
        if (empty($class)) {
            $class = "form";
        }

        $this->addCacheVar($this->urlAction);
        $url = $this->getLastCacheVar();

        $this->addCacheVar($this->name);
        $name = $this->getLastCacheVar();

        $this->addCacheVar($class);
        $class = $this->getLastCacheVar();

        $title = $this->getTitle($this->title);
        $description = $this->getDescription($this->description);

        $coreCache = CoreCache::getInstance(CoreCacheSection::FORMS);
        $content = "";

        if ($this->cached) { // Récupèration des données mise en cache
            $content = $coreCache->readCacheAsString($this->name . ".php",
                                                     self::CACHE_VARIABLE_NAME,
                                                     $this->cacheVars);
        } else { // Préparation puis mise en cache
            $data = "<form action=\"" . $url . "\" method=\"post\" id=\"form-" . $name . "\" name=\"" . $name . "\""
                . " class=\"" . $class . "\"><fieldset class=\"fieldset\">" . $title . $description . $this->inputData
                . (($this->doFieldset) ? "</fieldset>" : "") . "</form>";

            // Enregistrement dans le cache
            $data = $coreCache->serializeData($data);
            $content = $coreCache->writeCacheWithVariable($this->name . ".php",
                                                          $data,
                                                          true,
                                                          self::CACHE_VARIABLE_NAME,
                                                          $this->cacheVars);
        }
        return $content;
    }

    /**
     * Retourne le code HTML pour le label.
     *
     * @param string $name
     * @param string $description
     * @return string
     */
    private function &getLabel(string $name,
                               string $description): string
    {
        $id = $this->getId($name,
                           "input");
        $description = ExecString::textDisplay($description);
        $this->addCacheVar($description);

        $rslt = "";

        if (!$this->cached) {
            $rslt = "<label class=\"label\" for=\"" . $id . "\">" . $this->getLastCacheVar() . "</label>";
        }
        return $rslt;
    }

    /**
     * Retourne le code HTML pour le titre.
     *
     * @param string $title
     * @return string
     */
    private function &getTitle(string $title): string
    {
        $title = ExecString::textDisplay($title);
        $this->addCacheVar($title);

        $rslt = "";

        if (!$this->cached) {
            $rslt = "<legend class=\"legend\"><span class=\"text_bold\">" . $this->getLastCacheVar() . "</span></legend>";
        }
        return $rslt;
    }

    /**
     * Retourne le code HTML pour la description.
     *
     * @param string $description
     * @return string
     */
    private function &getDescription(string $description): string
    {
        $id = $this->getId("description");
        $description = ExecString::textDisplay($description);
        $this->addCacheVar($description);

        $rslt = "";

        if (!$this->cached) {
            $rslt = "<p class=\"" . $id . "\">" . $this->getLastCacheVar() . "</p>";
        }
        return $rslt;
    }

    /**
     * Retourne l'identifiant du champs.
     *
     * @param string $name
     * @param string $options
     * @return string
     */
    private function &getId(string $name,
                            string $options = ""): string
    {
        if (!empty($options)) {
            $options = "-" . $options;
        }

        $id = "form-" . $this->name . "-" . $name . $options;
        $this->addCacheVar($id);

        $rslt = "";

        if (!$this->cached) {
            $rslt = $this->getLastCacheVar();
        }
        return $rslt;
    }

    /**
     * Ajoute un champs.
     *
     * @param string $id
     * @param string $name
     * @param string $description
     * @param string $type
     * @param string $defaultValue
     * @param string $options
     * @param string $class
     */
    private function addInput(string $id,
                              string $name,
                              string $description,
                              string $type,
                              string $defaultValue = "",
                              string $options = "",
                              string $class = ""): void
    {
        if (empty($class)) {
            $class = "input";
        }

        $idDescription = $this->getId($name);
        $description = $this->getLabel($id,
                                       $description);
        $id = $this->getId($id,
                           "input");

        $this->addCacheVar($name);
        $name = $this->getLastCacheVar();

        $this->addCacheVar($type);
        $type = $this->getLastCacheVar();

        $this->addCacheVar($class);
        $class = $this->getLastCacheVar();

        $this->addCacheVar($defaultValue);
        $defaultValue = $this->getLastCacheVar();

        $this->addCacheVar($options);
        $options = $this->getLastCacheVar();

        if (!$this->cached) {
            $this->inputData .= "<p id=\"" . $idDescription . "\">" . $description
                . " <input id=\"" . $id . "\" name=\"" . $name . "\" type=\"" . $type . "\""
                . " class=\"" . $class . "\" value=\"" . $defaultValue . "\" " . $options . " />"
                . "</p>";
        }
    }

    /**
     * Ajoute d'une valeur en cache.
     *
     * @param string $value
     */
    private function addCacheVar(string $value): void
    {
        $this->cacheVars[$this->cacheVarsIndex++] = $value;
    }

    /**
     * Retourne la dernière variable de cache.
     *
     * @see CoreCache::getInstance()->getCache()
     * @return string
     */
    private function &getLastCacheVar(): string
    {
        $rslt = "";

        if (!$this->cached) {
            // Le nom de la "vars" est relatif a CoreCache::getInstance()->getCache()
            $rslt = "$" . self::CACHE_VARIABLE_NAME . "[" . ($this->cacheVarsIndex - 1) . "]";
        }
        return $rslt;
    }
}