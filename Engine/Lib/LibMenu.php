<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreHtml;
use TREngine\Engine\Core\CoreAccessType;
use TREngine\Engine\Core\CoreAccessZone;
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
     * Identifiant du menu.
     *
     * @var int
     */
    private $identifier = -1;

    /**
     * Identifiant du menu.
     *
     * @var int
     */
    private $identifier = -1;

    /**
     * L'ensemble des éléments du menu.
     *
     * @var LibMenuData[]
     */
    private $menuDatas = array();

    /**
     * Identifiant de l'élément de menu actif.
     *
     * @var int
     */
    private $activeMenuId = 0;

    /**
     * Construction du menu.
     *
     * @param int $accessZone Nom de la zone.
     * @param int $identifier Identifiant du contenu.
     * @param array $sql
     */
    public function __construct(int $identifier,
                                array $sql = array())
    {
        $this->identifier = $identifier;
        $this->activeMenuId = CoreRequest::getInteger("item",
                                                      0);

        if ($this->cached()) {
            $this->loadFromCache();
        } else if (count($sql) >= 3) {
            $this->loadFromDb($sql);
        }
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
        $activeItem = $this->getActiveMenuData();

        if ($activeItem !== null) {
            $activeTree = $activeItem->getTree();
        }

        foreach ($this->menuDatas as $key => $item) {
            $active = ExecUtils::inArray($key,
                                         $activeTree,
                                         true);
            if ($active) {
                $this->addAttribute("class",
                                    "active");
            }
        }

        // Début de rendu
        $out = "<ul id=\"" . $this->identifier . "\"" . $this->attributes . ">";

        // Rendu des branches principaux
        foreach ($this->menuDatas as $key => $item) {
            if ($item->getParentId() == 0) {
                // TODO // TODO
                $infos = array(
                    "zone" => CoreAccessZone::BLOCK,
                    "rank" => $item->getRank(),
                    "identifier" => $this->identifier);

                $menuAccessType = CoreAccessType::getTypeFromArray($this->menuDatas[$key]);

                if (CoreAccess::autorize($menuAccessType)) {
                    $out .= $this->menuDatas[$key]->render($callback);
                }
                // TODO // TODO
            }
        }
        $out .= "</ul>";

        if ($activeItem !== null) {
            $textWithRendering = $this->getActiveMenuData()->getTextWithRendering();
            LibBreadcrumb::getInstance()->addTrail($textWithRendering);
        }
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
                                                      $text);
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
     * Retourne l'élément de menu actif.
     *
     * @return LibMenuData
     */
    private function getActiveMenuData(): ?LibMenuData
    {
        $menuData = null;

        if (isset($this->menuDatas[$this->activeMenuId])) {
            $menuData = $this->menuDatas[$this->activeMenuId];
        }
        return $menuData;
    }

    /**
     * Chargement du menu via le cache.
     */
    private function loadFromCache(): void
    {
        $this->menuDatas = CoreCache::getInstance(CoreCacheSection::MENUS)->readCacheAsArrayUnserialized($this->identifier . ".php");
    }

    /**
     * Vérifie la présence du cache.
     *
     * @return bool
     */
    private function cached(): bool
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
            $menuArrayDatas = $coreSql->getBuffer($this->identifier);

            // Création de tous les menus
            foreach ($menuArrayDatas as $menuId => $data) {
                $this->menuDatas[$menuId] = new LibMenuData($data,
                                                            true);
            }

            // Création du chemin des menus
            foreach ($this->menuDatas as $menuData) {
                // Détermine le type de branche
                if ($menuData->getParentId() >= 0) {
                    // Enfant d'une branche
                    $menuData->addClassItemAttribute($menuData);
                    $this->menuDatas[$menuData->getParentId()]->addChild($menuData);
                } else {
                    // Branche principale
                    $menuData->addClassParentAttribute();
                }
            }

            CoreCache::getInstance(CoreCacheSection::MENUS)->writeCacheAsStringSerialize($this->identifier . ".php",
                                                                                         $this->menuDatas);
        }
    }
}