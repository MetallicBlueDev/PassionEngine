<?php

namespace PassionEngine\Engine\Lib;

use PassionEngine\Engine\Core\CoreDataStorage;
use PassionEngine\Engine\Core\CoreAccessToken;
use PassionEngine\Engine\Core\CoreAccessZone;
use PassionEngine\Engine\Core\CoreLoader;
use PassionEngine\Engine\Exec\ExecUtils;

/**
 * Collecteur d'information sur un menu.
 * Représente un élément de menu.
 *
 * @author Sébastien Villemain
 */
class LibMenuData extends CoreDataStorage implements CoreAccessToken
{

    /**
     * Un élement de menu parent.
     *
     * @var string
     */
    public const ITEM_PARENT = "parent";

    /**
     * Un élément de menu actif.
     *
     * @var string
     */
    public const ITEM_ACTIVE = "active";

    /**
     * Attributs de l'élément de menu.
     * Tableau à deux dimensions.
     *
     * @var array(attributeName => array(0 => value1))
     */
    private $attributes = array();

    /**
     * Balise relative à l'élément.
     *
     * @var array
     */
    private $tags = array();

    /**
     * Les enfants de l'élément.
     *
     * @var LibMenuData[]
     */
    private $children = array();

    /**
     * Nouvelle information pour un élément de menu.
     *
     * @param array $data
     */
    public function __construct(array &$data)
    {
        parent::__construct();

        $this->newStorage($data);
        $this->addTags("li");
    }

    /**
     * Nettoyage à la destruction.
     */
    public function __destruct()
    {
        $this->removeAllAttributes();
        $this->removeAllChildren();

        parent::__destruct();
    }

    /**
     * Identifiant de l'élément de menu.
     *
     * @return int
     */
    public function &getId(): int
    {
        return $this->getInt("menu_id",
                             -1);
    }

    /**
     * Nom l'élément de menu.
     *
     * @return string
     */
    public function &getName(): string
    {
        return "menu" . $this->getId();
    }

    /**
     * Retourne le rang l'élément de menu.
     *
     * @return int
     */
    public function &getRank(): int
    {
        return $this->getInt("rank",
                             0);
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function &getZone(): string
    {
        $zone = CoreAccessZone::MENU;
        return $zone;
    }

    /**
     * Retourne la configuration l'élément de menu.
     * Valeur nulle possible, notamment en base de données.
     *
     * @return array
     */
    public function &getConfigs(): array
    {
        return $this->getArray("menu_config");
    }

    /**
     * Retourne le texte l'élément de menu.
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
     * Retourne le texte mise en forme de l'élément de menu.
     *
     * @return array
     */
    public function &getTextWithRendering(): string
    {
        return $this->getString("text_with_rendering");
    }

    /**
     * Identifiant du block lié à cet élément de menu.
     *
     * @return int
     */
    public function &getBlockId(): int
    {
        return $this->getInt("block_id");
    }

    /**
     * Identifiant l'élément de menu parent.
     * Retourne "-1" si aucun parent.
     *
     * @return int
     */
    public function &getParentId(): int
    {
        return $this->getInt("parent_id",
                             -1);
    }

    /**
     * Retourne le niveau actuel.
     *
     * @return int
     */
    public function &getSubLevel(): smallint
    {
        return $this->getInt("sublevel");
    }

    /**
     * Retourne la position.
     *
     * @return int
     */
    public function &getPosition(): int
    {
        return $this->getInt("position");
    }

    /**
     * Ajout de balise pour l'élément.
     *
     * @param string $tag
     */
    public function addTags(string $tag): void
    {
        $this->tags[] = $tag;
    }

    /**
     * Détermine si l'attribut existe.
     *
     * @param string $name Nom de l'attribut.
     * @return bool
     */
    public function &hasAttribute(string $name): bool
    {
        $rslt = isset($this->attributes[$name]);
        return $rslt;
    }

    /**
     * Détermine si la valeur de l'attribut existe.
     *
     * @param string $name Nom de l'attribut.
     * @param string $value Valeur de l'attribut.
     * @return bool
     */
    public function &hasAttributeValue(string $name,
                                       string $value): bool
    {
        return ExecUtils::inArrayStrictCaseInSensitive($value,
                                                       $this->attributs[$name]);
    }

    /**
     * Ajoute de l'attribut 'class' avec la valeur 'parent' à l'élément de menu.
     */
    public function addClassParentAttribute(): void
    {
        $this->addClassAttribute(self::ITEM_PARENT);
    }

    /**
     * Ajoute de l'attribut 'class' avec la valeur 'active' à l'élément de menu.
     */
    public function addClassActiveAttribute(): void
    {
        $this->addClassAttribute(self::ITEM_ACTIVE);

        foreach ($this->children as $child) {
            $child->addClassActiveAttribute();
        }
    }

    /**
     * Ajoute de l'attribut 'class' avec la valeur 'item'+identifiant à l'élément de menu.
     *
     * @param LibMenuData $menuData
     */
    public function addClassItemAttribute(LibMenuData $menuData): void
    {
        $this->addClassAttribute("item" . $menuData->getId());
    }

    /**
     * Ajoute de l'attribut 'class' à l'élément de menu.
     *
     * @param string $value Valeur de l'attribut.
     */
    public function addClassAttribute(string $value): void
    {
        $this->addAttribute("class",
                            $value);
    }

    /**
     * Ajoute un attribut à l'élément de menu.
     *
     * @param string $name Nom de l'attribut.
     * @param string $value Valeur de l'attribut.
     */
    public function addAttribute(string $name,
                                 string $value): void
    {
        if (!$this->hasAttribute($name)) {
            $this->insertAttribute($name,
                                   $value);
        } else {
            // Vérification des valeurs déjà enregistrées
            if (!$this->hasAttributeValue($name,
                                          $value)) {
                if ($value === self::ITEM_PARENT) {
                    $this->insertParentAttribute($name);
                } else if ($value === self::ITEM_ACTIVE) {
                    $this->insertActiveAttribute($name);
                } else {
                    $this->insertAttribute($name,
                                           $value);
                }
            }
        }
    }

    /**
     * Supprime un attribut.
     *
     * @param string $name Nom de l'attribut.
     */
    public function removeAttribute(string $name): void
    {
        if ($this->hasAttribute($name)) {
            $this->unsetAttribute($name);
        }
    }

    /**
     * Supprime tous les attributs.
     */
    public function removeAllAttributes(): void
    {
        foreach (array_keys($this->attributes) as $key) {
            $this->unsetAttribute($key);
        }
    }

    /**
     * Mise en forme des attributs.
     *
     * @return string
     */
    public function &renderAttributes(): string
    {
        return $this->renderMultiDimensionalAttributes($this->attributes);
    }

    /**
     * Détermine l'élement possède ce noeud enfant.
     *
     * @param LibMenuData $child
     * @return bool
     */
    public function &hasChild(LibMenuData &$child): bool
    {
        $rslt = isset($this->children[$child->getId()]);
        return $rslt;
    }

    /**
     * Ajoute un enfant à l'élément courant.
     *
     * @param LibMenuData $child
     */
    public function addChild(LibMenuData &$child): void
    {
        if (!$this->hasChild($child)) {
            $this->insertChild($child);
        }
    }

    /**
     * Supprime un enfant.
     *
     * @param LibMenuData $child
     */
    public function removeChild(?LibMenuData &$child = null): void
    {
        $this->unsetChild($child->getId());
    }

    /**
     * Supprime tous les enfants.
     */
    public function removeAllChildren(): void
    {
        foreach (array_keys($this->children) as $menuId) {
            $this->unsetChild($menuId);
        }
    }

    /**
     * Retourne une représentation de la classe en chaine de caractères.
     *
     * @param string $callback
     * @return string
     */
    public function &render(string $callback = ""): string
    {
        $this->renderText($callback);

        // Préparation des données
        $out = "";
        $end = "";
        $attribute = $this->renderAttributes();

        // Extraction des balises et ajout du texte
        foreach ($this->tags as $key => $tag) {
            $out .= "<" . $tag . $attribute . ">";

            if ($key === 0) {
                $out .= "<span>" . $this->getTextWithRendering() . "</span>";
            }

            $end = $end . "</" . $tag . ">";
        }

        // Ajout des enfants et des balises de fin
        $out .= $this->renderChildren($callback) . $end;
        return $out;
    }

    /**
     * Insertion à la suite de l'attribut.
     *
     * @param string $name Nom de l'attribut.
     * @param string $value Valeur de l'attribut.
     */
    private function insertAttribute(string $name,
                                     string $value): void
    {
        $this->attributs[$name][] = $value;
    }

    /**
     * Insertion en 1ère position de l'attribut.
     *
     * @param string $name Nom de l'attribut.
     * @param string $value Valeur de l'attribut.
     */
    private function insertParentAttribute(string $name): void
    {
        array_unshift($this->attributs[$name],
                      self::ITEM_PARENT);
    }

    /**
     * Insertion en 1ère position de l'attribut.
     *
     * @param string $name Nom de l'attribut.
     * @param string $value Valeur de l'attribut.
     */
    private function insertActiveAttribute(string $name): void
    {
        if ($this->attributs[$name][0] === self::ITEM_PARENT) {
            // Remplace parent par active
            $this->attributs[$name][0] = self::ITEM_ACTIVE;

            // Ajoute a nouveau parent en 1er
            $this->insertParentAttribute($name);
        } else {
            array_unshift($this->attributs[$name],
                          self::ITEM_ACTIVE);
        }
    }

    /**
     * Nettoyage en mémoire de l'attribut.
     *
     * @param string $name Nom de l'attribut.
     */
    public function unsetAttribute(string $name): void
    {
        unset($this->attributes[$name]);
    }

    /**
     * Insertion de l'enfant à l'élément courant.
     *
     * @param LibMenuData $child
     */
    private function insertChild(LibMenuData &$child): void
    {
        // Ajout du tag UL si c'est le 1er enfant
        if (empty($this->children)) {
            $this->addTags("ul");
        }

        // Ajoute la classe parent
        $this->addClassParentAttribute();

        // Ajoute la classe élément
        if ($this->getParentId() >= 0) {
            $this->addClassItemAttribute($this);
        }

        $this->children[$child->getId()] = &$child;
    }

    /**
     * Nettoyage en mémoire d'un enfant.
     *
     * @param int $menuId
     */
    private function unsetChild(int $menuId): void
    {
        unset($this->children[$menuId]);
    }

    /**
     * Mise en forme des attributs.
     *
     * @param array $multiDimensionalData
     * @return string
     */
    private function &renderMultiDimensionalAttributes(array $multiDimensionalData): string
    {
        $rslt = "";

        foreach ($multiDimensionalData as $key => $value) {
            $isAttributeName = !is_int($key);

            if ($isAttributeName) {
                $rslt .= " " . $key . "=\"";
            }

            if (is_array($value)) {
                $rslt .= $this->renderMultiDimensionalAttributes($value);
            } else {
                // Prochaine valeur d'attribut
                if (!empty($rslt) && !$isAttributeName) {
                    $rslt .= " ";
                }

                $rslt .= htmlspecialchars($value);
            }

            if ($isAttributeName) {
                $rslt .= "\"";
            }
        }
        return $rslt;
    }

    /**
     * Rendu du texte.
     *
     * @param string $callback
     */
    private function renderText(string $callback = ""): void
    {
        $text = $this->getText();

        // Mise en forme du texte via la callback
        if (!empty($callback) && !empty($text)) {
            $text = CoreLoader::callback($callback,
                                         $text,
                                         $this->getConfigs());
        }

        $this->setDataValue("text_with_rendering",
                            $text);
    }

    /**
     * Retourne le rendu des enfants.
     *
     * @param string $callback
     * @return string
     */
    public function &renderChildren(string $callback = ""): string
    {
        $out = "";

        if (!empty($this->children)) {
            foreach ($this->children as $child) {
                $out .= $child->render($callback);
            }
        }
        return $out;
    }
}