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
     * Nom permettant d'identifier le contenu du menu.
     *
     * @var string
     */
    private $menuFriendlyName = "";

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
    private $activeMenuId = -1;

    /**
     * Construction du menu.
     *
     * @param string $menuFriendlyName Nom permettant d'identifier le contenu du menu.
     * @param array $sql
     */
    public function __construct(string $menuFriendlyName,
                                array $sql = array())
    {
        $this->menuFriendlyName = $menuFriendlyName;
        $this->activeMenuId = CoreRequest::getInteger("activeMenuId",
                                                      -1);

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
        $activeMenuData = $this->getActiveMenuData();

        if ($activeMenuData !== null) {
            $activeMenuData->addClassActiveAttribute();
        }

        $out = "<ul id=\"" . $this->menuFriendlyName . "\">"
                . $this->renderMenuDatas($callback)
                . "</ul>";

        if ($activeMenuData !== null) {
            $textWithRendering = $activeMenuData->getTextWithRendering();
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
            $appliedKeys = array();

            // Application des options et styles
            foreach ($configs as $key => $value) {
                if (ExecUtils::inArrayStrictCaseInSensitive($key,
                                                            $appliedKeys)) {
                    continue;
                }

                $text = self::getLineText($text,
                                          $key,
                                          $value);
                $appliedKeys[] = $key;
            }

            $output = $text;
        } else {
            $output = $text;
        }
        return $output;
    }

    /**
     * Retourne le texte mise en forme.
     *
     * @param string $text Texte du lien.
     * @param array $key "BOLD","ITALIC","UNDERLINE"...
     * @param array $value "?" . CoreLayout::REQUEST_MODULE . "=home"
     * @return string
     */
    private static function &getLineText(string &$text,
                                         string $key,
                                         string $value): string
    {
        switch ($key) {
            case "BOLD":
                $text = "<span class=\"text_bold\">" . $text . "</span>";
                break;
            case "ITALIC":
                $text = "<span class=\"text_italic\">" . $text . "</span>";
                break;
            case "UNDERLINE":
                $text = "<span class=\"text_underline\">" . $text . "</span>";
                break;
            case "BIG":
                $text = "<span class=\"text_big\">" . $text . "</span>";
                break;
            case "SMALL":
                $text = "<span class=\"text_small\">" . $text . "</span>";
                break;
            case "A":
                $text = CoreHtml::getLink($value,
                                          $text);
                break;
        }
        return $text;
    }

    /**
     * Retourne l'élément de menu actif.
     *
     * @return LibMenuData
     */
    private function getActiveMenuData(): ?LibMenuData
    {
        $menuData = null;

        if ($this->activeMenuId >= 0 && isset($this->menuDatas[$this->activeMenuId])) {
            $menuData = $this->menuDatas[$this->activeMenuId];
        }
        return $menuData;
    }

    /**
     * Chargement du menu via le cache.
     */
    private function loadFromCache(): void
    {
        $this->menuDatas = CoreCache::getInstance(CoreCacheSection::MENUS)->readCacheAsArrayUnserialized($this->menuFriendlyName . ".php");
    }

    /**
     * Vérifie la présence du cache.
     *
     * @return bool
     */
    private function cached(): bool
    {
        return (CoreCache::getInstance(CoreCacheSection::MENUS)->cached($this->menuFriendlyName . ".php"));
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
            $coreSql->addArrayBuffer($this->menuFriendlyName,
                                     "menu_id");
            $menuArrayDatas = $coreSql->getBuffer($this->menuFriendlyName);

            // TODO Chargement de la config du menu --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO
            $menuArrayDatas['menu_config'] = array();
// --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO --TODO
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

            CoreCache::getInstance(CoreCacheSection::MENUS)->writeCacheAsStringSerialize($this->menuFriendlyName . ".php",
                                                                                         $this->menuDatas);
        }
    }

    /**
     * Retourne le rendu des éléments de menu.
     *
     * @param string $callback
     * @return string
     */
    private function &renderMenuDatas(string $callback): string
    {
        $out = "";

        foreach ($this->menuDatas as $menuData) {
            if ($menuData->getParentId() >= 0) {
                continue;
            }

            $menuAccessType = CoreAccessType::getTypeFromToken($menuData);

            if (CoreAccess::autorize($menuAccessType)) {
                $out .= $menuData->render($callback);
            }
        }
        return $out;
    }
}