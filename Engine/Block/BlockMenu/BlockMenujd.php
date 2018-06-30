<?php

namespace TREngine\Engine\Block;

use TREngine\Engine\Block\BlockMenu\BlockMenu;
use TREngine\Engine\Core\CoreHtml;
use TREngine\Engine\Exec\ExecJQuery;
use TREngine\Engine\Lib\LibMakeStyle;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

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
            $menus->addAttributs("class",
                                 "jd_menu" . (($this->getBlockData()
                            ->getSide() == 1 || $this->getBlockData()
                            ->getSide() == 2) ? " jd_menu_vertical" : ""));
        }

        $libMakeStyle = new LibMakeStyle();
        $libMakeStyle->assignString("blockTitle",
                                    $this->getBlockData()
                        ->getTitle());
        $libMakeStyle->assignString("blockContent",
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