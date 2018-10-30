<?php

namespace PassionEngine\Engine\Lib;

use PassionEngine\Engine\Core\CoreHtml;
use PassionEngine\Engine\Core\CoreAccessType;
use PassionEngine\Engine\Core\CoreAccess;
use PassionEngine\Engine\Core\CoreCacheSection;
use PassionEngine\Engine\Core\CoreRequest;
use PassionEngine\Engine\Core\CoreSql;
use PassionEngine\Engine\Core\CoreTable;
use PassionEngine\Engine\Core\CoreCache;
use PassionEngine\Engine\Exec\ExecString;
use PassionEngine\Engine\Exec\ExecUtils;

/**
 * Gestionnaire de menu.
 *
 * @author Sébastien Villemain
 */
class LibMenu
{

    /**
     * Identifiant du block sur lequel le menu est lié.
     *
     * @var int
     */
    private $blockId = -1;

    /**
     * Nom permettant d'identifier le contenu du menu.
     *
     * @var string
     */
    private $menuFriendlyName = '';

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
     * @param int $blockId Identifiant du block.
     */
    public function __construct(int $blockId)
    {
        $this->blockId = $blockId;
        $this->menuFriendlyName = 'block' . $blockId;
        $this->activeMenuId = CoreRequest::getInteger('activeMenuId',
                                                      -1);

        if ($this->cached()) {
            $this->loadFromCache();
        } else {
            $this->loadFromDb();
        }
    }

    /**
     * Création d'un rendu complet du menu.
     *
     * @param string $callback
     * @return string
     */
    public function &render(string $callback = ''): string
    {
        if (empty($callback)) {
            $callback = LibMenuStyle::DEFAULT_LINE_RENDERING_METHOD;
        }

        $activeMenuData = $this->getActiveMenuData();

        if ($activeMenuData !== null) {
            $activeMenuData->addClassActiveAttribute();
        }

        $out = '<ul id="' . $this->menuFriendlyName . '">'
            . $this->renderMenuDatas($callback)
            . '</ul>';

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
     * @param array $configs array('BOLD'=>1,'ITALIC'=>1,'UNDERLINE'=>1,'A'=>'?' . CoreLayout::REQUEST_MODULE . '=home')
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
     * @param array $key 'BOLD','ITALIC','UNDERLINE'...
     * @param array $value '?' . CoreLayout::REQUEST_MODULE . '=home'
     * @return string
     */
    private static function &getLineText(string &$text,
                                         string $key,
                                         string $value): string
    {
        switch ($key) {
            // no break
            case LibMenuStyle::BOLD:
            case LibMenuStyle::ITALIC:
            case LibMenuStyle::UNDERLINE:
            case LibMenuStyle::BIG:
            case LibMenuStyle::SMALL:
                $styleName = 'text_' . strtolower($key);
                $text = '<span class="' . $styleName . '>' . $text . '</span>';
                break;
            case LibMenuStyle::HYPER_LINK:
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
        $this->menuDatas = CoreCache::getInstance(CoreCacheSection::MENUS)->readCacheAsArrayUnserialized($this->menuFriendlyName . '.php');
    }

    /**
     * Vérifie la présence du cache.
     *
     * @return bool
     */
    private function cached(): bool
    {
        return (CoreCache::getInstance(CoreCacheSection::MENUS)->cached($this->menuFriendlyName . '.php'));
    }

    /**
     * Chargement du menu depuis la base.
     */
    private function loadFromDb(): void
    {
        $coreSql = CoreSql::getInstance()->getSelectedBase();
        $coreSql->select(CoreTable::MENUS,
                         array('menu_id', 'block_id', 'parent_id', 'sublevel', 'position', 'rank'),
                         array('block_id = \'' . $this->blockId . '\''),
                         array('sublevel', 'parent_id', 'position'))->query();

        if ($coreSql->affectedRows() > 0) {
            // Création d'une mémoire tampon pour les menus
            $coreSql->addArrayBuffer($this->menuFriendlyName,
                                     'menu_id');
            $menuArrayDatas = $coreSql->getBuffer($this->menuFriendlyName);

            $coreSql->select(CoreTable::MENUS_CONFIGS,
                             array(CoreTable::MENUS_CONFIGS . '.menu_id', 'name', 'value'),
                             array('block_id = \'' . $this->blockId . '\''),
                             array(CoreTable::MENUS_CONFIGS . '.menu_id'))
                ->innerJoin(CoreTable::MENUS,
                            CoreTable::MENUS_CONFIGS,
                            'menu_id',
                            '=')
                ->query();

            $coreSql->addArrayBuffer($this->menuFriendlyName . 'config',
                                     'menu_id');
            $menuConfigArrayDatas = $coreSql->getBuffer($this->menuFriendlyName . 'config');
            $coreSql->freeBuffer();

            // Création de tous les menus
            foreach ($menuArrayDatas as $menuId => $data) {
                $data['menu_config'] = ExecUtils::getArrayConfigs($menuConfigArrayDatas[$menuId]);
                $this->menuDatas[$menuId] = new LibMenuData($data,
                                                            true);
            }

            $this->buildMenu();
            $this->writeMenuCache();
        }
    }

    /**
     * Création du chemin des menus.
     */
    private function buildMenu(): void
    {
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
    }

    /**
     * Enregistre les menus dans le cache.
     */
    private function writeMenuCache(): void
    {
        CoreCache::getInstance(CoreCacheSection::MENUS)->writeCacheAsStringSerialize($this->menuFriendlyName . '.php',
                                                                                     $this->menuDatas);
    }

    /**
     * Retourne le rendu des éléments de menu.
     *
     * @param string $callback
     * @return string
     */
    private function &renderMenuDatas(string $callback): string
    {
        $out = '';

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