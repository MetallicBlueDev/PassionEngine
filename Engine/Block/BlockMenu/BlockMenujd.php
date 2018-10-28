<?php

namespace PassionEngine\Engine\Block;

use PassionEngine\Engine\Block\BlockMenu\BlockMenu;
use PassionEngine\Engine\Core\CoreHtml;
use PassionEngine\Engine\Exec\ExecJQuery;
use PassionEngine\Engine\Lib\LibMakeStyle;

/**
 * Block de menu style jdMenu by Jonathan Sharp
 *
 * @author Sébastien Villemain
 */
class BlockMenujd extends BlockMenu
{

    public function display()
    {
        ExecJQuery::checkJdMenu();

        $menus = $this->getMenu();
        if (CoreHtml::getInstance()->javascriptEnabled()) {
            $menus->addAttribute('class',
                                 'jd_menu' . (($this->getBlockData()
                    ->getSide() == 1 || $this->getBlockData()
                    ->getSide() == 2) ? ' jd_menu_vertical' : ''));
        }

        $libMakeStyle = new LibMakeStyle();
        $libMakeStyle->assignString('blockTitle',
                                    $this->getBlockData()
                ->getTitle());
        $libMakeStyle->assignString('blockContent',
                                    $menus->render());
        $libMakeStyle->display($this->getBlockData()
                ->getTemplateName());
    }

    public function install()
    {

    }

    public function uninstall()
    {
        parent::uninstall();
    }
}