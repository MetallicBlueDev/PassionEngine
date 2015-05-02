<?php

namespace TREngine\Engine\Block;

use TREngine\Engine\Lib\LibMakeStyle;
use TREngine\Engine\Exec\ExecJQuery;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Block de menu style Menu treeview by
 *
 * @author Sébastien Villemain
 */
class BlockMenutree extends BlockMenu {

    public function display() {
        $this->configure();
        $menus = $this->getMenu();
        $menus->addAttributs("class", "treeview");

        $libMakeStyle = new LibMakeStyle();
        $libMakeStyle->assign("blockTitle", $this->getBlockData()->getTitle());
        $libMakeStyle->assign("blockContent", $menus->render());
        $libMakeStyle->display($this->getBlockData()->getTemplateName());
    }

    private function configure() {
        // Configure le style pour la classe
        $this->getBlockData()->setContent(strtolower($this->getBlockData()->getContent()));

        switch ($this->getBlockData()->getContent()) {
            case "black":
            case "red":
            case "gray":
            case "famfamfam":
                break;
            default:
                $this->getBlockData()->setContent("");
        }

        ExecJQuery::getTreeView("#block" . $this->getBlockData()->getId());
    }

    public function install() {

    }

    public function uninstall() {
        parent::uninstall();
    }

}
