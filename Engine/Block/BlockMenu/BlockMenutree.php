<?php

namespace TREngine\Engine\Block;

use TREngine\Engine\Block\BlockMenu\BlockMenu;
use TREngine\Engine\Exec\ExecJQuery;
use TREngine\Engine\Lib\LibMakeStyle;

/**
 * Block de menu style Menu treeview by
 *
 * @author Sébastien Villemain
 */
class BlockMenutree extends BlockMenu
{

    public function display()
    {
        $this->configure();
        $menus = $this->getMenu();
        $menus->addAttribute('class',
                             'treeview');

        $libMakeStyle = new LibMakeStyle();
        $libMakeStyle->assignString('blockTitle',
                                    $this->getBlockData()->getTitle());
        $libMakeStyle->assignString('blockContent',
                                    $menus->render());
        $libMakeStyle->display($this->getBlockData()->getTemplateName());
    }

    private function configure()
    {
        $configs = $this->getBlockData()->getConfigs();

        if (isset($configs['type'])) {
            switch (strtolower($configs['type'])) {
                case 'black':
                case 'red':
                case 'gray':
                case 'famfamfam':
                    break;
            }
        }

        ExecJQuery::checkTreeView('#block' . $this->getBlockData()->getId());
    }

    public function install()
    {

    }

    public function uninstall()
    {
        parent::uninstall();
    }
}