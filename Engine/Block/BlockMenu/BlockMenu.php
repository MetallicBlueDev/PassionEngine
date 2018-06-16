<?php

namespace TREngine\Engine\Block\BlockMenu;

use TREngine\Engine\Block\BlockModel;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreCacheSection;
use TREngine\Engine\Core\CoreTable;
use TREngine\Engine\Lib\LibMakeStyle;
use TREngine\Engine\Lib\LibMenu;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Block de menu.
 *
 * @author Sébastien Villemain
 */
class BlockMenu extends BlockModel {

    public function display() {
        $menus = $this->getMenu();

        $libMakeStyle = new LibMakeStyle();
        $libMakeStyle->assign("blockTitle", $this->getBlockData()->getTitle());
        $libMakeStyle->assign("blockContent", "<nav class=\"menu\">" . $menus->render() . "</nav>");
        $libMakeStyle->display($this->getBlockData()->getTemplateName());
    }

    public function install() {

    }

    public function uninstall() {
        CoreCache::getInstance(CoreCacheSection::MENUS)->removeCache("block" . $this->getBlockData()->getId() . ".php");
    }

    protected function getMenu() {
        $menus = new LibMenu(
                "block" . $this->getBlockData()->getId(), array(
            "table" => CoreTable::MENUS,
            "select" => array(
                "menu_id",
                "block_id",
                "parent_id",
                "content",
                "sublevel",
                "position",
                "rank"),
            "where" => array(
                "block_id = '" . $this->getBlockData()->getId() . "'"),
            "orderby" => array(
                "sublevel",
                "parent_id",
                "position"),
            "limit" => array()
                )
        );
        return $menus;
    }
}