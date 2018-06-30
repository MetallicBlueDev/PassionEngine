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
        $menus->addAttributs("class",
                             "treeview");

        $libMakeStyle = new LibMakeStyle();
        $libMakeStyle->assignString("blockTitle",
                                    $this->getBlockData()
                        ->getTitle());
        $libMakeStyle->assignString("blockContent",
                                    $menus->render());
        $libMakeStyle->display($this->getBlockData()
                        ->getTemplateName());
    }

    private function configure()
    {
        // Configure le style pour la classe
        $this->getBlockData()->setContent(strtolower($this->getBlockData()
                                ->getContent()));

        switch ($this->getBlockData()->getContent()) {
            case "black":
            case "red":
            case "gray":
            case "famfamfam":
                break;
            default:
                $this->getBlockData()->setContent("");
        }

        ExecJQuery::checkTreeView("#block" . $this->getBlockData()->getId());
    }

    public function install()
    {

    }

    public function uninstall()
    {
        parent::uninstall();
    }
}