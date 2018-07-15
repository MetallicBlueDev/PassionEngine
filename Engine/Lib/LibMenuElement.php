<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreDataStorage;
use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Exec\ExecUtils;

/**
 * Membre d'un menu.
 *
 * @author Sébastien Villemain
 */
class LibMenuElement extends CoreDataStorage
{

    /**
     * Attributs de l'élément.
     *
     * @var array
     */
    private $attributs = array();

    /**
     * Enfant de l'élément.
     *
     * @var array
     */
    private $child = array();

    /**
     * Balise relative à l'élément.
     *
     * @var array
     */
    private $tags = array();

    /**
     * Chemin complet de l'élément.
     *
     * @var array
     */
    private $tree = array();

    /**
     * Détermine si l'élément est actif.
     *
     * @var bool
     */
    private $active = false;

    /**
     * Construction de l'élément du menu
     *
     * @param array $item
     * @param array $initializeConfig
     */
    public function __construct(array $item,
                                bool $initializeConfig = false)
    {
        parent::__construct();

        if ($initializeConfig) {
            $data['menu_config'] = isset($data['menu_config']) ? ExecUtils::getArrayConfigs($data['menu_config']) : array();
        }

        $this->newStorage($item);
        $this->addTags("li");
    }

    /**
     * Retourne l'identifiant du menu parent.
     * Valeur nulle possible, notamment en base de données.
     *
     * @return int
     */
    public function &getParentId(): int
    {
        return $this->getInt("parent_id");
    }

    /**
     * Retourne l'identifiant du menu.
     *
     * @return int
     */
    public function &getMenuId(): int
    {
        return $this->getInt("menu_id");
    }

    /**
     * Retourne la configuration du menu.
     * Valeur nulle possible, notamment en base de données.
     *
     * @return array
     */
    public function &getConfigs(): array
    {
        return $this->getArray("menu_config");
    }

    /**
     * Retourne le texte du menu.
     * Valeur nulle possible, notamment en base de données.
     *
     * @return array
     */
    public function &getText(): array
    {
        return $this->getSubString("menu_config",
                                   "text",
                                   "");
    }

    /**
     * Retourne le rang d'accès du menu.
     *
     * @return int
     */
    public function &getRank(): int
    {
        return $this->getInt("rank");
    }

    /**
     * Affecte le chemin vers cet élément.
     *
     * @param array $tree
     */
    public function setTree(array &$tree): void
    {
        $this->tree = $tree;
    }

    /**
     * Retourne le chemin vers cet élément.
     *
     * @return array
     */
    public function &getTree(): array
    {
        return $this->tree;
    }

    /**
     * Ajoute un attribut à la liste.
     *
     * @param string $name nom de l'attribut
     * @param string $value valeur de l'attribut
     */
    public function addAttributs(string $name,
                                 string $value): void
    {
        if (!isset($this->attributs[$name])) {
            $this->attributs[$name] = $value;
        } else {
            // Conversion en tableau si besoin
            if (!is_array($this->attributs[$name])) {
                $firstValue = $this->attributs[$name];
                $this->attributs[$name] = array();
                $this->attributs[$name][] = $firstValue;
            }

            // Vérification des valeurs déjà enregistrées
            if (!ExecUtils::inArray($value,
                                    $this->attributs[$name],
                                    true)) {
                if ($value === "parent") {
                    array_unshift($this->attributs[$name],
                                  $value);
                } else if ($value === "active") {
                    if ($this->attributs[$name][0] === "parent") {
                        // Remplace parent par active
                        $this->attributs[$name][0] = $value;
                        // Ajoute a nouveau parent en 1er
                        array_unshift($this->attributs[$name],
                                      "parent");
                    } else {
                        array_unshift($this->attributs[$name],
                                      $value);
                    }
                } else {
                    $this->attributs[$name][] = $value;
                }
            }
        }
    }

    /**
     * Supprime un attributs.
     *
     * @param string $name nom de l'attribut
     */
    public function removeAttributs(string $name = ""): void
    {
        if (!empty($name)) {
            unset($this->attributs[$name]);
        } else {
            foreach (array_keys($this->attributs) as $key) {
                unset($this->attributs[$key]);
            }
        }
    }

    /**
     * Mise en forme des attributs.
     *
     * @param array $attributs
     * @return string
     */
    public function &getAttributs(array $attributs = array()): string
    {
        $rslt = "";
        $attributs = empty($attributs) ? $this->attributs : $attributs;

        foreach ($attributs as $attributsName => $value) {
            $isInt = is_int($attributsName);

            if (!$isInt) {
                $rslt .= " " . $attributsName . "=\"";
            }

            if (is_array($value)) {
                $rslt .= $this->getAttributs($value);
            } else {
                if (!empty($rslt) && $isInt) {
                    $rslt .= " ";
                }

                $rslt .= htmlspecialchars($value);
            }

            if (!$isInt) {
                $rslt .= "\"";
            }
        }
        return $rslt;
    }

    /**
     * Détermine si l'élément est actif.
     *
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * Ajout de balise tag pour l'élément.
     *
     * @param string $tag
     */
    public function addTags(string $tag): void
    {
        $this->tags[] = $tag;
    }

    /**
     * Ajoute un enfant à l'item courant.
     *
     * @param LibMenuElement  $child
     */
    public function addChild(LibMenuElement &$child): void
    {
        // Ajout du tag UL si c'est un nouveau parent
        if (empty($this->child)) {
            $this->addTags("ul");
        }

        // Ajoute la classe parent
        $this->addAttributs("class",
                            "parent");

        // Ajoute la classe élément
        if ($this->getParentId() > 0) {
            $this->addAttributs("class",
                                "item" . $this->getMenuId());
        }

        $this->child[$child->getMenuId()] = &$child;

        $tree = $child->getTree();
        $tree[] = $this->getMenuId();
        $child->setTree($tree);
    }

    /**
     * Supprime un enfant.
     *
     * @param LibMenuElement $child
     */
    public function removeChild(?LibMenuElement &$child = null): void
    {
        if ($child === null) {
            foreach (array_keys($this->child) as $key) {
                unset($this->child[$key]);
            }
        } else {
            unset($this->child[$child->getMenuId()]);
        }
    }

    /**
     * Retourne une représentation de la classe en chaine de caractères.
     *
     * @param string $callback
     * @return string
     */
    public function &toString(string $callback = ""): string
    {
        $text = $this->getText();

        // Mise en forme du texte via la callback
        if (!empty($callback) && !empty($text)) {
            $text = CoreLoader::callback($callback,
                                         $text,
                                         $this->getConfigs());
        }

        // Ajout de la classe active
        if ($this->active) {
            $this->addAttributs("class",
                                "active");
            LibBreadcrumb::getInstance()->addTrail($text);
        }

        // Préparation des données
        $out = "";
        $end = "";
        $attributs = $this->getAttributs();
        $text = "<span>" . $text . "</span>";

        // Extraction des balises de débuts et de fin et ajout du texte
        foreach ($this->tags as $tag) {
            $out .= "<" . $tag . $attributs . ">" . $text;
            $text = "";
            $end = $end . "</" . $tag . ">";
        }

        // Constuction des branches
        if (!empty($this->child)) {
            foreach ($this->child as $child) {
                $out .= $child->toString($callback);
            }
        }

        // Ajout des balises de fin
        $out .= $end;
        return $out;
    }

    /**
     * Nettoyage à la destruction.
     */
    public function __destruct()
    {
        $this->item = array();
        $this->removeAttributs();
        $this->removeChild();
    }
}