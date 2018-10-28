<?php

namespace PassionEngine\Engine\Block\BlockMenu;

use PassionEngine\Engine\Block\BlockModel;
use PassionEngine\Engine\Core\CoreCache;
use PassionEngine\Engine\Core\CoreCacheSection;
use PassionEngine\Engine\Lib\LibMakeStyle;
use PassionEngine\Engine\Lib\LibMenu;

/**
 * Block de menu.
 *
 * @author Sébastien Villemain
 */
class BlockIndex extends BlockModel
{

    public function display()
    {
        $menus = $this->getMenu();

        $libMakeStyle = new LibMakeStyle();
        $libMakeStyle->assignString('blockTitle',
                                    $this->getBlockData()->getTitle());
        $libMakeStyle->assignString('blockContent',
                                    '<nav class=\'menu\'>' . $menus->render() . '</nav>');
        $libMakeStyle->display($this->getBlockData()->getTemplateName());
    }

    public function install()
    {

    }

    public function uninstall()
    {
        CoreCache::getInstance(CoreCacheSection::MENUS)->removeCache('block' . $this->getBlockData()->getId() . '.php');
    }

    protected function getMenu()
    {
        $menus = new LibMenu($this->getBlockData()->getId());
        return $menus;
    }
}