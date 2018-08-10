<?php

namespace TREngine\Engine\Block;

use TREngine\Engine\Core\CoreMain;
use TREngine\Engine\Exec\ExecUtils;

/**
 * Block login, accès rapide à une connexion, à une déconnexion et à son compte.
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
        $content = "";
        return $content;
    }

    public function display()
    {
        $this->configure();

        // Si le module courant fait partie de la liste des affichages
        if (ExecUtils::inArray(CoreMain::getInstance()->getRoute()->getRequestedModuleData()->getName(),
                               $this->displayOnModule,
                               true)) {
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