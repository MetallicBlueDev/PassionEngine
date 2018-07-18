<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreHtml;
use TREngine\Engine\Core\CoreAccessType;
use TREngine\Engine\Core\CoreAccess;
use TREngine\Engine\Core\CoreCacheSection;
use TREngine\Engine\Core\CoreRequest;
use TREngine\Engine\Core\CoreSql;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Exec\ExecString;
use TREngine\Engine\Exec\ExecUtils;

/**
 * Gestionnaire de menu.
 *
 * @author Sébastien Villemain
 */
class LibMenu
{

    /**
     * Balise pour les options.
     *
     * @var string
     */
    private const OPTIONS_TAG = "__OPTIONS__";

    /**
     * Identifiant du menu.
     *
     * @var string
     */
    private $identifier = "";

    /**
     * Menu complet et son arborescence.
     *
     * @var array
     */
    private $items = array();

    /**
     * Identifiant de l'élément actuellement actif.
     *
     * @var int
     */
    private $activeItemId = 0;

    /**
     * Attributs de l'élément principal.
     *
     * @var string
     */
    private $attribtus = "";

    /**
     * Construction du menu.
     *
     * @param string $identifier Identifiant du menu par exemple "block22"
     * @param array $sql
     */
    public function __construct(string $identifier,
                                array $sql = array())
    {
        $this->identifier = $identifier;
        $this->activeItemId = CoreRequest::getInteger("item",
                                                      0);

        if ($this->isCached()) {
            $this->loadFromCache();
        } else if (count($sql) >= 3) {
            $this->loadFromDb($sql);
        }
    }

    /**
     * Ajoute un attribut à l'élément UL principal.
     *
     * @param string $name
     * @param string $value
     */
    public function addAttributs(string $name,
                                 string $value): void
    {
        $this->attribtus .= " " . $name . "=\"" . $value . "\"";
    }

    /**
     * Création d'un rendu complet du menu.
     *
     * @param string $callback
     * @return string
     */
    public function &render(string $callback = "LibMenu::getLine"): string
    {
        $activeTree = array();
        $activeItem = $this->getActiveItem();

        if ($activeItem !== null) {
            $activeTree = $activeItem->getTree();
        }

        foreach ($this->items as $key => $item) {
            $item->setActive(ExecUtils::inArray($key,
                                                $activeTree,
                                                true));
        }

        // Début de rendu
        $out = "<ul id=\"" . $this->identifier . "\"" . $this->attribtus . ">";

        // Rendu des branches principaux
        foreach ($this->items as $key => $item) {
            if ($item->getParentId() == 0) {
                $infos = array(
                    "zone" => "MENU",
                    "rank" => $item->getRank(),
                    "identifier" => $this->identifier);

                if (CoreAccess::autorize(CoreAccessType::getTypeFromDatas($infos))) {
                    $out .= $this->items[$key]->toString($callback);
                }
            }
        }
        $out .= "</ul>";
        return $out;
    }

    /**
     * Retourne une ligne de menu propre sous forme HTML.
     *
     * @param string $text Texte du lien.
     * @param array $configs array("BOLD"=>1,"ITALIC"=>1,"UNDERLINE"=>1,"A"=>"?" . CoreLayout::REQUEST_MODULE . "=home")
     * @return string
     */
    public static function getLine(string $text,
                                   array $configs): string
    {
        $text = ExecString::textDisplay($text);

        if (!empty($configs)) {
            $bold = false;
            $italic = false;
            $underline = false;
            $big = false;
            $small = false;
            $link = false;

            // Application des options et styles
            foreach ($configs as $key => $value) {
                switch ($key) {
                    case "BOLD":
                        if (!$bold) {
                            $text = "<span class=\"text_bold\">" . $text . "</span>";
                            $bold = true;
                        }
                        break;
                    case "ITALIC":
                        if (!$italic) {
                            $text = "<span class=\"text_italic\">" . $text . "</span>";
                            $italic = true;
                        }
                        break;
                    case "UNDERLINE":
                        if (!$underline) {
                            $text = "<span class=\"text_underline\">" . $text . "</span>";
                            $underline = true;
                        }
                        break;
                    case "BIG":
                        if (!$big) {
                            $text = "<span class=\"text_big\">" . $text . "</span>";
                            $big = true;
                        }
                        break;
                    case "SMALL":
                        if (!$small) {
                            $text = "<span class=\"text_small\">" . $text . "</span>";
                            $small = true;
                        }
                        break;
                    case "A":
                        if (!$link) {
                            $text = CoreHtml::getLink($value,
                                                      $text,
                                                      false);
                            $link = true;
                        }
                        break;
                }
            }

            $output = $text;
        } else {
            $output = $text;
        }
        return $output;
    }

    /**
     * Retourne l'élément actif.
     *
     * @return LibMenuElement
     */
    private function getActiveItem(): ?LibMenuElement
    {
        $item = null;

        if (isset($this->items[$this->activeItemId]) && is_object($this->items[$this->activeItemId])) {
            $item = $this->items[$this->activeItemId];
        }
        return $item;
    }

    /**
     * Chargement du menu via le cache.
     */
    private function loadFromCache(): void
    {
        $this->items = CoreCache::getInstance(CoreCacheSection::MENUS)->readCacheAsArrayUnserialized($this->identifier . ".php");
    }

    /**
     * Vérifie la présence du cache.
     *
     * @return bool
     */
    private function isCached(): bool
    {
        return (CoreCache::getInstance(CoreCacheSection::MENUS)->cached($this->identifier . ".php"));
    }

    /**
     * Chargement du menu depuis la base.
     *
     * @param array $sql parametre de Sélection
     */
    private function loadFromDb(array $sql): void
    {
        $coreSql = CoreSql::getInstance();

        $coreSql->select(
            $sql['table'],
            $sql['select'],
            $sql['where'],
            $sql['orderby'],
            $sql['limit']
        );

        if ($coreSql->affectedRows() > 0) {
            // Création d'une mémoire tampon pour les menus
            $coreSql->addArrayBuffer($this->identifier,
                                     "menu_id");
            $menus = $coreSql->getBuffer($this->identifier);

            // Création de tous les menus
            foreach ($menus as $key => $item) {
                $this->items[$key] = new LibMenuElement($item,
                                                        true);
            }

            // Création du chemin des menus
            foreach ($this->items as $item) {
                // Détermine le type de branche
                if ($item->getParentId() > 0) {
                    // Enfant d'une branche
                    $item->addAttributs("class",
                                        "item" . $item->getMenuId());
                    $this->items[$item->getParentId()]->addChild($item);
                } else if ($item->getParentId() == 0) {
                    // Branche principal
                    $item->addAttributs("class",
                                        "parent");
                }

                // Termine le chemin du menu avec son propre identifiant
                $tree = $item->getTree();
                $tree[] = $item->getMenuId();
                $item->setTree($tree);
            }

            CoreCache::getInstance(CoreCacheSection::MENUS)->writeCacheAsStringSerialize($this->identifier . ".php",
                                                                                         $this->items);
        }
    }
}