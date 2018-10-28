<?php

namespace PassionEngine\Engine\Block;

use PassionEngine\Engine\Core\CoreMain;
use PassionEngine\Engine\Exec\ExecUtils;

/**
 *
 * @author Sébastien Villemain
 */
class BlockIndex extends BlockModel
{

    private $displayOnModule = array();

    private function configure()
    {
        $this->displayOnModule = $this->getBlockData()->getConfigs();
    }

    private function &render()
    {
        $content = '';
        return $content;
    }

    public function display()
    {
        $this->configure();

        // Si le module courant fait partie de la liste des affichages
        if (ExecUtils::inArrayStrictCaseInSensitive(CoreMain::getInstance()->getRoute()->getRequestedModuleData()->getName(),
                                                    $this->displayOnModule)) {
            // Si la position est interieur au module (moduletop ou modulebottom)
            if ($this->getBlockData()->getSide() == 5 || $this->getBlockData()->getSide() == 6) {
                echo $this->render();
            }
        }
    }

    public function install()
    {

    }

    public function uninstall()
    {

    }
}